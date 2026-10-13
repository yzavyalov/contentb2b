<?php

namespace App\Services;

use App\Models\Bet;

class MarketPayloadService
{
    public function __construct(
        private MarketCountryPayloadService $countryPayloadService
    ) {
    }

    public function full(Bet $bet): array
    {
        $bet->loadMissing([
            'translations',
            'answers.translations',
            'sources',
            'categories',
            'countries',
            'countryGroups.countries',
        ]);

        $status = $bet->status instanceof \BackedEnum
            ? $bet->status->value
            : $bet->status;

        $imageUrl = filled($bet->image_path)
            ? asset('storage/'.$bet->image_path)
            : null;

        return [
            'id' => $bet->id,
            'status' => $status,
            'source_locale' => $bet->source_locale,
            'image_url' => $imageUrl,
            'finish_at' => $bet->finish_at?->toIso8601String(),
            'published_at' => $bet->published_at?->toIso8601String(),

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

            'countries' =>
                $this->countryPayloadService->forBet($bet),
        ];
    }
}
