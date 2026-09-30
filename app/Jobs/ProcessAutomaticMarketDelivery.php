<?php

namespace App\Jobs;

use App\Enums\MerchantBetDeliverySource;
use App\Enums\MerchantBetStatus;
use App\Models\Bet;
use App\Models\MerchantBet;
use App\Models\MerchantDeliveryRule;
use App\Services\MerchantDeliveryRuleMatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Queue\Queueable;

class ProcessAutomaticMarketDelivery implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(
        public int $betId
    ) {
    }

    public function handle(
        MerchantDeliveryRuleMatcher $matcher
    ): void {
        /*
        |--------------------------------------------------------------------------
        | LOAD MARKET
        |--------------------------------------------------------------------------
        */

        $bet = Bet::query()
            ->with([
                'categories:id',
                'countries:id',
                'countryGroups:id',
                'translations:id,bet_id,locale',
            ])
            ->find($this->betId);

        if (! $bet) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | ONLY PUBLISHED
        |--------------------------------------------------------------------------
        */

        $status = $bet->status instanceof \BackedEnum
            ? $bet->status->value
            : (string) $bet->status;

        if ($status !== 'published') {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | ACTIVE RULES
        |--------------------------------------------------------------------------
        */

        $rules = MerchantDeliveryRule::query()
            ->where('is_active', true)
            ->with([
                'merchant',
                'categories:id',
                'countries:id',
                'countryGroups:id',
                'locales:id,merchant_delivery_rule_id,locale',
            ])
            ->get();

        if ($rules->isEmpty()) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | FIND UNIQUE MATCHED MERCHANTS
        |--------------------------------------------------------------------------
        |
        | Если один market совпадает с несколькими rules одного merchant,
        | merchant всё равно получает market только один раз.
        |
        */

        $matchedMerchants = [];

        foreach ($rules as $rule) {
            $merchant = $rule->merchant;

            if (! $merchant) {
                continue;
            }

            /*
             * Без Market URL доставлять market некуда.
             */

            if (! $merchant->market_url) {
                continue;
            }

            if (! $matcher->matches($bet, $rule)) {
                continue;
            }

            $matchedMerchants[(int) $merchant->id] = $merchant;
        }

        if ($matchedMerchants === []) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | CREATE MERCHANT BET + DISPATCH DELIVERY
        |--------------------------------------------------------------------------
        */

        foreach ($matchedMerchants as $merchant) {
            /*
             * Сначала делаем быструю проверку.
             *
             * Она убирает почти все обычные повторные запуски
             * ProcessAutomaticMarketDelivery.
             */

            $existingMerchantBet = MerchantBet::query()
                ->where('merchant_id', $merchant->id)
                ->where('bet_id', $bet->id)
                ->first();

            if ($existingMerchantBet) {
                continue;
            }

            /*
             * Теперь пытаемся создать MerchantBet.
             *
             * В БД существует UNIQUE:
             *
             * merchant_id + bet_id
             *
             * Поэтому даже если два workers одновременно дошли
             * до этого места, физически будет создана только
             * одна запись.
             */

            try {
                $merchantBet = $merchant
                    ->merchantBets()
                    ->create([
                        'bet_id' => $bet->id,

                        'status' =>
                            MerchantBetStatus::QUEUED->value,

                        'delivery_source' =>
                            MerchantBetDeliverySource::AUTOMATIC->value,

                        /*
                         * Billing snapshot будет установлен
                         * существующим SendMarketToMerchant.
                         */

                        'billing_mode' => null,
                        'unit_price' => 0,
                        'charged_amount' => 0,
                    ]);
            } catch (QueryException $e) {
                /*
                 * Возможная race condition:
                 *
                 * Worker A:
                 * SELECT -> записи нет
                 *
                 * Worker B:
                 * SELECT -> записи нет
                 *
                 * Worker A:
                 * INSERT -> OK
                 *
                 * Worker B:
                 * INSERT -> UNIQUE violation
                 *
                 * Если запись уже существует, значит другой
                 * worker выиграл гонку. Для этого job работа
                 * закончена и повторная доставка не нужна.
                 */

                $merchantBetAlreadyExists = MerchantBet::query()
                    ->where('merchant_id', $merchant->id)
                    ->where('bet_id', $bet->id)
                    ->exists();

                if ($merchantBetAlreadyExists) {
                    continue;
                }

                /*
                 * Если MerchantBet не появился, значит QueryException
                 * возник по другой причине.
                 *
                 * Такое исключение нельзя скрывать.
                 */

                throw $e;
            }

            /*
            |--------------------------------------------------------------------------
            | SEND MARKET
            |--------------------------------------------------------------------------
            |
            | Только worker, который действительно создал MerchantBet,
            | имеет право поставить SendMarketToMerchant в очередь.
            |
            | Поэтому:
            |
            | 2 AUTO jobs
            |      ↓
            | 2 workers
            |      ↓
            | только один INSERT
            |      ↓
            | только один SendMarketToMerchant
            |
            */

            SendMarketToMerchant::dispatch(
                $merchantBet->id
            );
        }
    }
}
