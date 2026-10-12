<?php

namespace App\Services;

use App\Models\Bet;

class MarketCountryPayloadService
{
    /**
     * Возвращает плоский список всех стран рынка.
     *
     * Если рынок привязан непосредственно к странам,
     * они попадают в результат.
     *
     * Если рынок привязан к группам стран,
     * каждая группа разворачивается в свои страны.
     *
     * Дубликаты удаляются по ISO-коду страны.
     */
    public function forBet(Bet $bet): array
    {
        $bet->loadMissing([
            'countries',
            'countryGroups.countries',
        ]);

        $countries = $bet->countries
            ->concat(
                $bet->countryGroups->flatMap(
                    fn ($group) => $group->countries
                )
            )
            ->filter(
                fn ($country) =>
                filled($country->code)
            )
            ->unique(
                fn ($country) =>
                strtoupper($country->code)
            )
            ->sortBy(
                fn ($country) =>
                mb_strtolower($country->name)
            )
            ->map(
                fn ($country) => [
                    'code' => strtoupper($country->code),
                    'name' => $country->name,
                ]
            )
            ->values();

        return $countries->all();
    }
}
