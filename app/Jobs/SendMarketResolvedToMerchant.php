<?php

namespace App\Jobs;

use App\Enums\MerchantBetStatus;
use App\Enums\MerchantCallbackEvent;
use App\Enums\MerchantCallbackStatus;
use App\Models\MerchantBet;
use App\Models\MerchantCallback;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
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

    public function handle(): void
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
        if ($bet->resolved_at === null || $bet->winning_answer_id === null) {
            throw new RuntimeException(
                "Bet #{$bet->id} is not resolved yet."
            );
        }

        /*
         * callback_url is used ONLY for subsequent market events:
         * resolution, cancellation, updates, etc.
         */
        $callbackUrl = $merchant->callback_url
            ? trim($merchant->callback_url)
            : null;

        if (! $callbackUrl) {
            MerchantCallback::query()->updateOrCreate(
                [
                    'merchant_bet_id' => $merchantBet->id,
                    'event' => MerchantCallbackEvent::MARKET_RESOLVED,
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
                'event' => MerchantCallbackEvent::MARKET_RESOLVED,
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
         *
         * If this resolution was already successfully delivered,
         * never send it again.
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
         * For a retry use the merchant's CURRENT callback_url.
         *
         * This means Finance/Admin can correct a broken URL
         * and retry the callback.
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

        $payload = [
            'event' => MerchantCallbackEvent::MARKET_RESOLVED->value,

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
                        'is_winner' =>
                            (int) $answer->id
                            === (int) $bet->winning_answer_id,

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

                'winning_answer' => $bet->winningAnswer
                    ? [
                        'id' => $bet->winningAnswer->id,

                        'translations' =>
                            $bet->winningAnswer->translations
                                ->map(fn ($translation) => [
                                    'locale' => $translation->locale,
                                    'title' => $translation->title,
                                ])
                                ->values()
                                ->all(),
                    ]
                    : null,

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
            $response = Http::acceptJson()
                ->asJson()
                ->timeout(20)
                ->post($callbackUrl, $payload);

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
             *
             * The merchant already paid for the market according
             * to the applicable billing plan.
             */
            $callback->update([
                'status' => MerchantCallbackStatus::DELIVERED,
                'http_status' => $response->status(),
                'billable' => false,
                'delivered_at' => $now,
                'last_error' => null,
            ]);

            /*
             * MerchantBet.resolved_at means that the resolution
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
