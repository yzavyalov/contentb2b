<?php

namespace App\Jobs;

use App\Enums\MerchantBetStatus;
use App\Enums\MerchantCallbackEvent;
use App\Enums\MerchantCallbackStatus;
use App\Models\MerchantBet;
use App\Models\MerchantCallback;
use App\Services\MerchantSignedHttpService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use RuntimeException;
use Throwable;

class SendMarketResolvedToMerchant implements ShouldQueue
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
         * Resolution callback may only be sent for a market
         * that was previously delivered to this merchant.
         */
        if ($merchantBet->delivered_at === null) {
            throw new RuntimeException(
                "MerchantBet #{$merchantBet->id} was not delivered. "
                . 'Resolution callback cannot be sent.'
            );
        }

        /*
         * The market must actually be resolved.
         */
        if (
            $bet->resolved_at === null
            || $bet->winning_answer_id === null
        ) {
            throw new RuntimeException(
                "Bet #{$bet->id} is not resolved yet."
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
                        MerchantCallbackEvent::MARKET_RESOLVED,
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
         * One MARKET_RESOLVED callback per MerchantBet.
         */
        $callback = MerchantCallback::query()->firstOrCreate(
            [
                'merchant_bet_id' => $merchantBet->id,
                'event' =>
                    MerchantCallbackEvent::MARKET_RESOLVED,
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
         * Successfully delivered resolution callback
         * is never sent twice.
         */
        if ($callback->delivered_at !== null) {
            if ($merchantBet->resolved_at === null) {
                $merchantBet->update([
                    'resolved_at' => $callback->delivered_at,
                ]);
            }

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

        $status = $bet->status instanceof \BackedEnum
            ? $bet->status->value
            : $bet->status;

        /*
         * market.resolved contains only lifecycle/result data.
         *
         * winning_answer_id is the single source of truth
         * for the winning outcome.
         */
        $payload = [
            'event' =>
                MerchantCallbackEvent::MARKET_RESOLVED->value,

            'market' => [
                'id' => $bet->id,
                'status' => $status,
                'winning_answer_id' =>
                    $bet->winning_answer_id,
                'resolved_at' =>
                    $bet->resolved_at->toIso8601String(),
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
                    'Resolution callback returned HTTP '
                    . $response->status();

                $callback->update([
                    'status' => MerchantCallbackStatus::FAILED,
                    'http_status' => $response->status(),
                    'billable' => false,
                    'last_error' => $error,
                ]);

                throw new RuntimeException($error);
            }

            $now = now();

            /*
             * Resolution callback is NOT separately billable.
             */
            $callback->update([
                'status' => MerchantCallbackStatus::DELIVERED,
                'http_status' => $response->status(),
                'billable' => false,
                'delivered_at' => $now,
                'last_error' => null,
            ]);

            /*
             * MerchantBet.resolved_at means that resolution
             * was successfully delivered to THIS merchant.
             */
            $merchantBet->update([
                'status' => MerchantBetStatus::RESOLVED,
                'resolved_at' => $now,
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
