<?php

namespace App\Services;

use App\Enums\MerchantBillingPeriodStatus;
use App\Models\Merchant;
use App\Models\MerchantBillingPeriod;
use App\Models\MerchantSubscription;

class MerchantBillingPeriodService
{
    /**
     * Find or create the billing period that contains the current moment.
     *
     * Database uniqueness:
     *
     * merchant_subscription_id + period_start
     *
     * guarantees that Scheduler and webhook processing cannot create
     * duplicate billing periods for the same subscription cycle.
     */
    public function getOrCreateCurrentPeriod(
        Merchant $merchant,
        MerchantSubscription $subscription,
        int $includedMarkets
    ): MerchantBillingPeriod {
        /*
         * First check whether the current period already exists.
         */
        $period = MerchantBillingPeriod::query()
            ->where('merchant_id', $merchant->id)
            ->where('merchant_subscription_id', $subscription->id)
            ->where('period_start', '<=', now())
            ->where('period_end', '>=', now())
            ->latest('period_start')
            ->first();

        if ($period) {
            return $period;
        }

        /*
         * Billing periods are anchored to the subscription start.
         *
         * Example:
         *
         * starts_at:
         * 2026-09-22 00:00:00
         *
         * periods:
         * 2026-09-22 -> 2026-10-22
         * 2026-10-22 -> 2026-11-22
         * etc.
         */
        $start = $subscription->starts_at
            ? $subscription->starts_at->copy()
            : now()->startOfDay();

        while ($start->copy()->addMonth() <= now()) {
            $start->addMonth();
        }

        $end = $start->copy()->addMonth();

        /*
         * A billing period cannot extend beyond the subscription.
         */
        if (
            $subscription->ends_at
            && $subscription->ends_at->lt($end)
        ) {
            $end = $subscription->ends_at->copy();
        }

        /*
         * Subscription custom price overrides the plan price.
         */
        $monthlyFee = $this->money(
            $subscription->custom_monthly_fee
            ?? $subscription->billingPlan->monthly_fee
            ?? 0
        );

        /*
         * firstOrCreate works together with the UNIQUE database index:
         *
         * merchant_subscription_id + period_start
         *
         * Modern Laravel handles the race where another process inserts
         * the same unique record between SELECT and INSERT.
         */
        return MerchantBillingPeriod::query()->firstOrCreate(
            [
                'merchant_subscription_id' => $subscription->id,
                'period_start' => $start,
            ],
            [
                'merchant_id' => $merchant->id,

                'period_end' => $end,

                'included_markets' => $includedMarkets,
                'markets_used' => 0,

                'monthly_fee' => $monthlyFee,
                'monthly_fee_charged_at' => null,
                'monthly_fee_transaction_id' => null,

                'overage_amount' => 0,

                'status' => MerchantBillingPeriodStatus::OPEN,
            ]
        );
    }

    private function money(mixed $value): float
    {
        return round((float) $value, 2);
    }
}
