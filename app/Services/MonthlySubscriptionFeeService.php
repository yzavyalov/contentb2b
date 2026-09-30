<?php

namespace App\Services;

use App\Enums\MerchantBalanceTransactionType;
use App\Models\MerchantBalanceTransaction;
use App\Models\MerchantBillingPeriod;
use App\Models\MerchantWallet;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MonthlySubscriptionFeeService
{
    public function charge(MerchantBillingPeriod $billingPeriod): void
    {
        DB::transaction(function () use ($billingPeriod) {
            /*
             * Блокируем billing period.
             * Это защищает от двойного списания, если два worker/job
             * одновременно попытаются обработать один период.
             */
            $period = MerchantBillingPeriod::query()
                ->whereKey($billingPeriod->id)
                ->lockForUpdate()
                ->firstOrFail();

            /*
             * Уже списывали monthly fee — ничего больше не делаем.
             */
            if ($period->monthly_fee_charged_at !== null) {
                return;
            }

            $amount = $this->money($period->monthly_fee);

            /*
             * Бесплатный subscription/hybrid plan.
             * Денежную транзакцию на $0 создавать не нужно,
             * но период считаем обработанным.
             */
            if ($amount <= 0) {
                $period->update([
                    'monthly_fee_charged_at' => now(),
                ]);

                return;
            }

            $currency = 'USD';

            /*
             * Блокируем кошелёк на время изменения баланса.
             */
            $wallet = MerchantWallet::query()
                ->where('merchant_id', $period->merchant_id)
                ->where('currency', $currency)
                ->lockForUpdate()
                ->first();

            if (! $wallet) {
                throw new RuntimeException(
                    "Merchant #{$period->merchant_id} has no {$currency} wallet."
                );
            }

            $before = $this->money($wallet->balance);
            $after = $this->money($before - $amount);

            if ($after < 0) {
                throw new RuntimeException(
                    "Merchant #{$period->merchant_id} has insufficient {$currency} balance for monthly subscription fee."
                );
            }

            $wallet->update([
                'balance' => $after,
            ]);

            $transaction = MerchantBalanceTransaction::query()->create([
                'merchant_id' => $period->merchant_id,
                'type' => MerchantBalanceTransactionType::SUBSCRIPTION_CHARGE,
                'amount' => $amount,
                'balance_before' => $before,
                'balance_after' => $after,
                'currency' => $currency,

                'reference_type' => MerchantBillingPeriod::class,
                'reference_id' => $period->id,

                'description' => "Monthly subscription fee for billing period #{$period->id}.",

                /*
                 * Автоматическое системное списание.
                 */
                'created_by_user_id' => null,
            ]);

            $period->update([
                'monthly_fee_charged_at' => now(),
                'monthly_fee_transaction_id' => $transaction->id,
            ]);
        }, 3);
    }

    private function money(mixed $value): float
    {
        return round((float) $value, 2);
    }
}
