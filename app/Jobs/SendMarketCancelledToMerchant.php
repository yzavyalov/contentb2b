<?php

namespace App\Jobs;

use App\Enums\BetStatus;
use App\Enums\MerchantCallbackEvent;
use App\Enums\MerchantCallbackStatus;
use App\Models\MerchantBet;
use App\Models\MerchantCallback;
use App\Services\MerchantSignedHttpService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use RuntimeException;
use Throwable;

class SendMarketCancelledToMerchant implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 30;

    public function __construct(
        public int $merchantBetId
    ) {
    }

    public function handle(
        MerchantSignedHttpService $signedHttp
    ): void {
        $merchantBet = MerchantBet::query()
            ->with([
                'merchant',
                'bet',
            ])
            ->findOrFail($this->merchantBetId);

        $merchant = $merchantBet->merchant;
        $bet = $merchantBet->bet;

        /*
         * Cancellation callback may only be sent for a market
         * that was previously delivered to this merchant.
         */
        if ($merchantBet->delivered_at === null) {
            throw new RuntimeException(
                "MerchantBet #{$merchantBet->id} was not delivered. "
                . 'Cancellation callback cannot be sent.'
            );
        }

        /*
         * The Bet must actually be cancelled.
         */
        $betStatus = $bet->status instanceof \BackedEnum
            ? $bet->status->value
            : $bet->status;

        if ($betStatus !== BetStatus::CANCELLED->value) {
            throw new RuntimeException(
                "Bet #{$bet->id} is not cancelled."
            );
        }

        /*
         * A cancelled market must not have a final winner.
         */
        if (
            $bet->resolved_at !== null
            || $bet->winning_answer_id !== null
        ) {
            throw new RuntimeException(
                "Bet #{$bet->id} already has a final resolution."
            );
        }

        /*
         * Lifecycle callbacks use callback_url.
         */
        $callbackUrl = $merchant->callback_url
            ? trim($merchant->callback_url)
            : null;

        if (! $callbackUrl) {
            MerchantCallback::query()->updateOrCreate(
                [
                    'merchant_bet_id' => $merchantBet->id,
                    'event' =>
                        MerchantCallbackEvent::MARKET_CANCELLED,
                ],
                [
                    'merchant_id' => $merchant->id,
                    'bet_id' => $bet->id,
                    'status' => MerchantCallbackStatus::FAILED,
                    'callback_url' => null,
                    'billable' => false,
                    'last_error' =>
                        'Merchant callback URL is not configured.',
                ]
            );

            return;
        }

        /*
         * One MARKET_CANCELLED callback per MerchantBet.
         */
        $callback = MerchantCallback::query()->firstOrCreate(
            [
                'merchant_bet_id' => $merchantBet->id,
                'event' =>
                    MerchantCallbackEvent::MARKET_CANCELLED,
            ],
            [
                'merchant_id' => $merchant->id,
                'bet_id' => $bet->id,
                'status' => MerchantCallbackStatus::PENDING,
                'callback_url' => $callbackUrl,
                'attempts_count' => 0,
                'billable' => false,
            ]
        );

        /*
         * Successfully delivered cancellation callback
         * is never sent twice.
         */
        if ($callback->delivered_at !== null) {
            return;
        }

        /*
         * Retry uses the merchant's CURRENT callback_url.
         */
        $callback->update([
            'status' => MerchantCallbackStatus::PROCESSING,
            'callback_url' => $callbackUrl,
            'attempts_count' => $callback->attempts_count + 1,
            'last_error' => null,
        ]);

        /*
         * Cancellation is a lifecycle event.
         *
         * No market content is repeated here.
         */
        $payload = [
            'event' =>
                MerchantCallbackEvent::MARKET_CANCELLED->value,

            'market' => [
                'id' => $bet->id,
                'status' => $betStatus,
            ],
        ];

        try {
            $response = $signedHttp->post(
                $merchant,
                $callbackUrl,
                $payload
            );

            if (! $response->successful()) {
                $error =
                    'Cancellation callback returned HTTP '
                    . $response->status();

                $callback->update([
                    'status' => MerchantCallbackStatus::FAILED,
                    'http_status' => $response->status(),
                    'billable' => false,
                    'last_error' => $error,
                ]);

                throw new RuntimeException($error);
            }

            /*
             * market.cancelled is not separately billable.
             */
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
