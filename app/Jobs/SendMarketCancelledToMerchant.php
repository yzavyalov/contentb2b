<?php

namespace App\Jobs;

use App\Enums\BetStatus;
use App\Enums\MerchantCallbackEvent;
use App\Enums\MerchantCallbackStatus;
use App\Models\MerchantBet;
use App\Models\MerchantCallback;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Services\MerchantSignedHttpService;
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

    public function handle(MerchantSignedHttpService $signedHttp): void
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
        if ($bet->resolved_at !== null || $bet->winning_answer_id !== null) {
            throw new RuntimeException(
                "Bet #{$bet->id} already has a final resolution."
            );
        }

        $callbackUrl = $merchant->callback_url
            ? trim($merchant->callback_url)
            : null;

        /*
         * Keep a callback record even when the merchant
         * has no callback URL configured.
         */
        if (! $callbackUrl) {
            MerchantCallback::query()->updateOrCreate(
                [
                    'merchant_bet_id' => $merchantBet->id,
                    'event' => MerchantCallbackEvent::MARKET_CANCELLED,
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
                'event' => MerchantCallbackEvent::MARKET_CANCELLED,
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
         * Idempotency:
         * successfully delivered cancellation callback
         * is never sent twice.
         */
        if ($callback->delivered_at !== null) {
            return;
        }

        /*
         * Retry always uses the merchant's CURRENT callback_url.
         */
        $callback->update([
            'status' => MerchantCallbackStatus::PROCESSING,
            'callback_url' => $callbackUrl,
            'attempts_count' => $callback->attempts_count + 1,
            'last_error' => null,
        ]);

        $payload = [
            'event' => MerchantCallbackEvent::MARKET_CANCELLED->value,

            'market' => [
                'id' => $bet->id,
                'status' => $betStatus,
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
                        'is_winner' => false,

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

                'winning_answer' => null,

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
