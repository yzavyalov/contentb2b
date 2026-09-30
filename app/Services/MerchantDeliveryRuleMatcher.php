<?php

namespace App\Services;

use App\Models\Bet;
use App\Models\MerchantDeliveryRule;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class MerchantDeliveryRuleMatcher
{
    public function matches(Bet $bet, MerchantDeliveryRule $rule): bool
    {
        /*
         * Неактивное правило никогда не участвует
         * в автоматической доставке.
         */
        if (! $rule->is_active) {
            return false;
        }

        /*
         * Загружаем данные, необходимые для проверки.
         */
        $bet->loadMissing([
            'categories:id',
            'countries:id',
            'countryGroups:id',
            'translations:id,bet_id,locale',
        ]);

        $rule->loadMissing([
            'categories:id',
            'countries:id',
            'countryGroups:id',
            'locales:id,merchant_delivery_rule_id,locale',
        ]);

        /*
         * CATEGORY
         *
         * Пустой список в rule = любая категория.
         * Если категории выбраны — достаточно совпадения
         * хотя бы одной категории.
         */
        if ($rule->categories->isNotEmpty()) {
            $allowedIds = $rule->categories
                ->pluck('id')
                ->map(fn ($id) => (int) $id);

            $betIds = $bet->categories
                ->pluck('id')
                ->map(fn ($id) => (int) $id);

            if ($allowedIds->intersect($betIds)->isEmpty()) {
                return false;
            }
        }

        /*
         * COUNTRY
         *
         * Пустой список = любая страна.
         * Если страны выбраны — достаточно совпадения
         * хотя бы одной страны.
         */
        if ($rule->countries->isNotEmpty()) {
            $allowedIds = $rule->countries
                ->pluck('id')
                ->map(fn ($id) => (int) $id);

            $betIds = $bet->countries
                ->pluck('id')
                ->map(fn ($id) => (int) $id);

            if ($allowedIds->intersect($betIds)->isEmpty()) {
                return false;
            }
        }

        /*
         * COUNTRY GROUP
         *
         * Пустой список = любая группа стран.
         * Если группы выбраны — достаточно совпадения
         * хотя бы одной группы.
         */
        if ($rule->countryGroups->isNotEmpty()) {
            $allowedIds = $rule->countryGroups
                ->pluck('id')
                ->map(fn ($id) => (int) $id);

            $betIds = $bet->countryGroups
                ->pluck('id')
                ->map(fn ($id) => (int) $id);

            if ($allowedIds->intersect($betIds)->isEmpty()) {
                return false;
            }
        }

        /*
         * LOCALE
         *
         * Пустой список = любой язык.
         * Market может иметь несколько переводов.
         * Если locales выбраны — достаточно хотя бы
         * одного совпадающего перевода.
         */
        if ($rule->locales->isNotEmpty()) {
            $allowedLocales = $rule->locales
                ->pluck('locale')
                ->map(
                    fn ($locale) =>
                    strtolower(trim((string) $locale))
                );

            $betLocales = $bet->translations
                ->pluck('locale')
                ->map(
                    fn ($locale) =>
                    strtolower(trim((string) $locale))
                );

            if ($allowedLocales->intersect($betLocales)->isEmpty()) {
                return false;
            }
        }

        /*
         * FINISH DATE
         *
         * Auto Delivery никогда не должен отправлять
         * market без finish_at или уже завершившийся market.
         *
         * Эта проверка выполняется ВСЕГДА —
         * независимо от того, установлены ли
         * min_finish_hours / max_finish_hours.
         */
        if (! $bet->finish_at) {
            return false;
        }

        $finishAt = $bet->finish_at;

        if (! $finishAt instanceof CarbonInterface) {
            $finishAt = Carbon::parse($finishAt);
        }

        /*
         * Уже завершившийся market не подходит.
         */
        if ($finishAt->lte(now())) {
            return false;
        }

        /*
         * Количество часов до завершения market.
         *
         * Используем дробное значение, чтобы избежать
         * ошибок округления на границах диапазона.
         */
        $hoursUntilFinish = now()->diffInSeconds(
                $finishAt,
                false
            ) / 3600;

        /*
         * MINIMUM FINISH HOURS
         */
        if (
            $rule->min_finish_hours !== null &&
            $hoursUntilFinish < $rule->min_finish_hours
        ) {
            return false;
        }

        /*
         * MAXIMUM FINISH HOURS
         */
        if (
            $rule->max_finish_hours !== null &&
            $hoursUntilFinish > $rule->max_finish_hours
        ) {
            return false;
        }

        /*
         * Все заполненные критерии правила прошли.
         */
        return true;
    }
}
