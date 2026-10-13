<?php

namespace App\Jobs;

use App\Enums\MerchantCallbackEvent;
use App\Enums\MerchantCallbackStatus;
use App\Models\MerchantCallback;
use App\Services\MarketPayloadService;
use App\Services\MerchantSignedHttpService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use RuntimeException;
use Throwable;

class SendMarketUpdatedToMerchant implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 30;

    public function __construct(
        public int $merchantCallbackId
    ) {
    }

    public function handle(
        MerchantSignedHttpService $signedHttp,
        MarketPayloadService $marketPayloadService
    ): void {
        $callback = MerchantCallback::query()
            ->with([
                'merchant',
                'merchantBet',
                'bet',
            ])
            ->findOrFail($this->merchantCallbackId);

        if ($callback->event !== MerchantCallbackEvent::MARKET_UPDATED) {
            throw new RuntimeException(
                "MerchantCallback #{$callback->id} is not a market.updated callback."
            );
        }

        if ($callback->delivered_at !== null) {
            return;
        }

        $merchant = $callback->merchant;
        $merchantBet = $callback->merchantBet;
        $bet = $callback->bet;

        if ($merchantBet->delivered_at === null) {
            throw new RuntimeException(
                "MerchantBet #{$merchantBet->id} was not delivered. "
                . 'Market update cannot be sent.'
            );
        }

        $marketUrl = $merchant->market_url
            ? trim($merchant->market_url)
            : null;

        if (! $marketUrl) {
            $callback->update([
                'status' => MerchantCallbackStatus::FAILED,
                'callback_url' => null,
                'billable' => false,
                'last_error' =>
                    'Merchant market delivery URL is not configured.',
            ]);

            return;
        }

        $callback->update([
            'status' => MerchantCallbackStatus::PROCESSING,
            'callback_url' => $marketUrl,
            'attempts_count' => $callback->attempts_count + 1,
            'billable' => false,
            'last_error' => null,
        ]);

        $payload = [
            'event' => MerchantCallbackEvent::MARKET_UPDATED->value,
            'market' => $marketPayloadService->full($bet),
        ];

        try {
            $response = $signedHttp->post(
                $merchant,
                $marketUrl,
                $payload
            );

            if (! $response->successful()) {
                $error =
                    'Market update endpoint returned HTTP '
                    . $response->status();

                $callback->update([
                    'status' => MerchantCallbackStatus::FAILED,
                    'http_status' => $response->status(),
                    'billable' => false,
                    'last_error' => $error,
                ]);

                throw new RuntimeException($error);
            }

            $callback->update([
                'status' => MerchantCallbackStatus::DELIVERED,
                'http_status' => $response->status(),
                'billable' => false,
                'delivered_at' => now(),
                'last_error' => null,
            ]);
        } catch (Throwable $e) {
            $callback->refresh();

            if ($callback->delivered_at === null) {
                $callback->update([
                    'status' => MerchantCallbackStatus::FAILED,
                    'billable' => false,
                    'last_error' => mb_substr(
                        $e->getMessage(),
                        0,
                        65000
                    ),
                ]);
            }

            throw $e;
        }
    }
}
