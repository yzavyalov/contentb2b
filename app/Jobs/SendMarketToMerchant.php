<?php

namespace App\Jobs;

use App\Enums\MerchantBetStatus;
use App\Enums\MerchantCallbackEvent;
use App\Enums\MerchantCallbackStatus;
use App\Models\MerchantBet;
use App\Models\MerchantCallback;
use App\Services\MerchantBillingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Services\MerchantSignedHttpService;
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
        MerchantSignedHttpService $signedHttp
    ): void
    {
        $merchantBet = MerchantBet::query()
            ->with([
                'merchant',
                'bet.translations',
                'bet.answers.translations',
                'bet.sources',
                'bet.categories',
                'bet.countries',
                'bet.countryGroups',
                'bet.winningAnswer.translations',
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
         * market_url is used ONLY for initial market delivery.
         *
         * Resolution/status callbacks will use callback_url
         * in a separate callback job/service.
         */
        $marketUrl = $merchant->market_url
            ? trim($merchant->market_url)
            : null;

        if (! $marketUrl) {
            $merchantBet->update([
                'status' => MerchantBetStatus::CALLBACK_FAILED,
                'callback_status' => MerchantCallbackStatus::FAILED->value,
            ]);

            MerchantCallback::query()->updateOrCreate(
                [
                    'merchant_bet_id' => $merchantBet->id,
                    'event' => MerchantCallbackEvent::MARKET_PUBLISHED,
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
         *
         * This is important because the merchant may change
         * market_url later, while the callback record should
         * preserve where this specific delivery was sent.
         */
        $callback = MerchantCallback::query()->firstOrCreate(
            [
                'merchant_bet_id' => $merchantBet->id,
                'event' => MerchantCallbackEvent::MARKET_PUBLISHED,
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
         *
         * This allows a financial/admin user to correct the URL
         * and retry the failed delivery.
         */
        $callback->update([
            'status' => MerchantCallbackStatus::PROCESSING,
            'callback_url' => $marketUrl,
            'attempts_count' => $callback->attempts_count + 1,
            'last_error' => null,
        ]);

        $status = $bet->status instanceof \BackedEnum
            ? $bet->status->value
            : $bet->status;

        $payload = [
            'event' => MerchantCallbackEvent::MARKET_PUBLISHED->value,

            'market' => [
                'id' => $bet->id,
                'status' => $status,
                'source_locale' => $bet->source_locale,
                'finish_at' => $bet->finish_at?->toIso8601String(),
                'published_at' => $bet->published_at?->toIso8601String(),
                'resolved_at' => $bet->resolved_at?->toIso8601String(),
                'winning_answer_id' => $bet->winning_answer_id,

                'translations' => $bet->translations
                    ->map(fn ($translation) => [
                        'locale' => $translation->locale,
                        'title' => $translation->title,
                        'description' => $translation->description,
                    ])
                    ->values()
                    ->all(),

                'answers' => $bet->answers
                    ->sortBy('sort_order')
                    ->map(fn ($answer) => [
                        'id' => $answer->id,
                        'sort_order' => $answer->sort_order,

                        'translations' => $answer->translations
                            ->map(fn ($translation) => [
                                'locale' => $translation->locale,
                                'title' => $translation->title,
                            ])
                            ->values()
                            ->all(),
                    ])
                    ->values()
                    ->all(),

                'sources' => $bet->sources
                    ->sortBy('sort_order')
                    ->map(fn ($source) => [
                        'url' => $source->url,
                        'sort_order' => $source->sort_order,
                    ])
                    ->values()
                    ->all(),

                'categories' => $bet->categories
                    ->map(fn ($category) => [
                        'id' => $category->id,
                        'name' => $category->name,
                        'slug' => $category->slug,
                    ])
                    ->values()
                    ->all(),

                'countries' => $bet->countries
                    ->map(fn ($country) => [
                        'id' => $country->id,
                        'code' => $country->code,
                        'name' => $country->name,
                    ])
                    ->values()
                    ->all(),

                'country_groups' => $bet->countryGroups
                    ->map(fn ($group) => [
                        'id' => $group->id,
                        'name' => $group->name,
                        'slug' => $group->slug,
                    ])
                    ->values()
                    ->all(),
            ],
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
             * The merchant has accepted the market.
             *
             * Persist successful delivery BEFORE billing.
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
         * Billing is performed only after successful delivery.
         */
        $billingService->chargeForMarket(
            $merchant,
            $merchantBet
        );
    }
}
