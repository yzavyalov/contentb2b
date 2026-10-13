<?php

namespace App\Jobs;

use App\Enums\MerchantBetStatus;
use App\Enums\MerchantCallbackEvent;
use App\Enums\MerchantCallbackStatus;
use App\Models\MerchantBet;
use App\Models\MerchantCallback;
use App\Services\MarketPayloadService;
use App\Services\MerchantBillingService;
use App\Services\MerchantSignedHttpService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use RuntimeException;
use Throwable;

class SendMarketToMerchant implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 30;

    public function __construct(
        public int $merchantBetId
    ) {
    }

    public function handle(
        MerchantBillingService $billingService,
        MerchantSignedHttpService $signedHttp,
        MarketPayloadService $marketPayloadService
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
         * Delivery and billing are separate idempotent stages.
         *
         * If delivery already succeeded but billing failed,
         * do NOT send the market again.
         *
         * Retry only the billing stage.
         */
        if ($merchantBet->delivered_at !== null) {
            if ($merchantBet->charged_at === null) {
                $billingService->chargeForMarket(
                    $merchant,
                    $merchantBet
                );
            }

            return;
        }

        /*
         * market_url is used for full market snapshots:
         *
         * market.published
         * market.updated
         *
         * Lifecycle callbacks use callback_url.
         */
        $marketUrl = $merchant->market_url
            ? trim($merchant->market_url)
            : null;

        if (! $marketUrl) {
            $merchantBet->update([
                'status' => MerchantBetStatus::CALLBACK_FAILED,
                'callback_status' =>
                    MerchantCallbackStatus::FAILED->value,
            ]);

            MerchantCallback::query()->updateOrCreate(
                [
                    'merchant_bet_id' => $merchantBet->id,
                    'event' =>
                        MerchantCallbackEvent::MARKET_PUBLISHED,
                ],
                [
                    'merchant_id' => $merchant->id,
                    'bet_id' => $bet->id,
                    'status' => MerchantCallbackStatus::FAILED,
                    'callback_url' => null,
                    'billable' => false,
                    'last_error' =>
                        'Merchant market delivery URL is not configured.',
                ]
            );

            return;
        }

        /*
         * Store the actual endpoint used for this delivery.
         */
        $callback = MerchantCallback::query()->firstOrCreate(
            [
                'merchant_bet_id' => $merchantBet->id,
                'event' =>
                    MerchantCallbackEvent::MARKET_PUBLISHED,
            ],
            [
                'merchant_id' => $merchant->id,
                'bet_id' => $bet->id,
                'status' => MerchantCallbackStatus::PENDING,
                'callback_url' => $marketUrl,
                'attempts_count' => 0,
                'billable' => false,
            ]
        );

        /*
         * The callback may already have been delivered while
         * merchant_bets was not fully synchronized.
         *
         * Normalize merchant_bets and continue to billing
         * without sending the market for a second time.
         */
        if ($callback->delivered_at !== null) {
            $merchantBet->update([
                'status' => MerchantBetStatus::DELIVERED,

                'delivered_at' =>
                    $merchantBet->delivered_at
                    ?? $callback->delivered_at,

                'callback_status' =>
                    MerchantCallbackStatus::DELIVERED->value,

                'callback_delivered_at' =>
                    $callback->delivered_at,
            ]);

            if ($merchantBet->charged_at === null) {
                $billingService->chargeForMarket(
                    $merchant,
                    $merchantBet
                );
            }

            return;
        }

        /*
         * For a pending/failed delivery retry, use the merchant's
         * CURRENT market_url.
         */
        $callback->update([
            'status' => MerchantCallbackStatus::PROCESSING,
            'callback_url' => $marketUrl,
            'attempts_count' => $callback->attempts_count + 1,
            'last_error' => null,
        ]);

        /*
         * market.published carries the complete authoritative
         * market snapshot.
         *
         * The same snapshot builder will later be reused by
         * market.updated.
         */
        $payload = [
            'event' =>
                MerchantCallbackEvent::MARKET_PUBLISHED->value,

            'market' =>
                $marketPayloadService->full($bet),
        ];

        try {
            $response = $signedHttp->post(
                $merchant,
                $marketUrl,
                $payload
            );

            if (! $response->successful()) {
                $error =
                    'Market delivery endpoint returned HTTP '
                    . $response->status();

                $callback->update([
                    'status' => MerchantCallbackStatus::FAILED,
                    'http_status' => $response->status(),
                    'billable' => false,
                    'last_error' => $error,
                ]);

                $merchantBet->update([
                    'status' => MerchantBetStatus::CALLBACK_FAILED,
                    'callback_status' =>
                        MerchantCallbackStatus::FAILED->value,
                ]);

                throw new RuntimeException($error);
            }

            /*
             * Persist successful delivery BEFORE billing.
             *
             * A billing error must never cause the external
             * market endpoint to receive this market twice.
             */
            $now = now();

            $callback->update([
                'status' => MerchantCallbackStatus::DELIVERED,
                'http_status' => $response->status(),
                'billable' => true,
                'delivered_at' => $now,
                'last_error' => null,
            ]);

            $merchantBet->update([
                'status' => MerchantBetStatus::DELIVERED,
                'delivered_at' => $now,
                'callback_status' =>
                    MerchantCallbackStatus::DELIVERED->value,
                'callback_delivered_at' => $now,
            ]);
        } catch (Throwable $e) {
            /*
             * Only delivery errors reach this block.
             *
             * Billing happens outside this try/catch, therefore
             * a billing failure cannot convert an already
             * delivered market into CALLBACK_FAILED.
             */
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

                $merchantBet->update([
                    'status' => MerchantBetStatus::CALLBACK_FAILED,
                    'callback_status' =>
                        MerchantCallbackStatus::FAILED->value,
                ]);
            }

            throw $e;
        }

        /*
         * Billing is performed only after successful
         * initial delivery.
         */
        $billingService->chargeForMarket(
            $merchant,
            $merchantBet
        );
    }
}
