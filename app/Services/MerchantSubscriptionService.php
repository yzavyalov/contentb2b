<?php

namespace App\Services;

use App\Enums\BillingType;
use App\Enums\MerchantBillingPeriodStatus;
use App\Enums\MerchantSubscriptionChangeReason;
use App\Enums\MerchantSubscriptionStatus;
use App\Models\BillingPlan;
use App\Models\Merchant;
use App\Models\MerchantBillingPeriod;
use App\Models\MerchantSubscription;
use App\Models\MerchantWallet;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MerchantSubscriptionService
{
    /**
     * Prepare a newly created merchant for billing.
     *
     * - creates USD wallet with zero balance;
     * - assigns the default Start plan.
     */
    public function initializeMerchant(Merchant $merchant): MerchantSubscription
    {
        return DB::transaction(function () use ($merchant) {

            MerchantWallet::query()->firstOrCreate(
                [
                    'merchant_id' => $merchant->id,
                    'currency' => 'USD',
                ],
                [
                    'balance' => 0,
                ]
            );

            /*
             * Do not create another active subscription
             * if the merchant has already been initialized.
             */
            $existingSubscription = MerchantSubscription::query()
                ->where('merchant_id', $merchant->id)
                ->where('status', MerchantSubscriptionStatus::ACTIVE)
                ->latest('id')
                ->first();

            if ($existingSubscription) {
                return $existingSubscription;
            }

            $defaultPlan = $this->getDefaultPlan();

            return MerchantSubscription::query()->create([
                'merchant_id' => $merchant->id,
                'billing_plan_id' => $defaultPlan->id,

                'status' => MerchantSubscriptionStatus::ACTIVE,

                'change_reason' =>
                    MerchantSubscriptionChangeReason::MERCHANT_CREATED,

                'starts_at' => now()->startOfDay(),
                'ends_at' => null,

                'custom_monthly_fee' => null,
                'custom_price_per_market' => null,
                'custom_included_markets' => null,
                'custom_overage_price' => null,
            ]);
        }, 3);
    }

    /**
     * Automatically move a merchant to the default Start plan.
     *
     * Used when a recurring subscription fee cannot be paid.
     */
    public function fallbackToDefaultPlan(
        MerchantSubscription $subscription
    ): MerchantSubscription {
        return DB::transaction(function () use ($subscription) {

            $lockedSubscription = MerchantSubscription::query()
                ->with('billingPlan')
                ->whereKey($subscription->id)
                ->lockForUpdate()
                ->firstOrFail();

            /*
             * Another process may already have changed
             * this subscription.
             */
            if (
                $lockedSubscription->status
                !== MerchantSubscriptionStatus::ACTIVE
            ) {
                $activeSubscription = MerchantSubscription::query()
                    ->where(
                        'merchant_id',
                        $lockedSubscription->merchant_id
                    )
                    ->where(
                        'status',
                        MerchantSubscriptionStatus::ACTIVE
                    )
                    ->latest('id')
                    ->first();

                if ($activeSubscription) {
                    return $activeSubscription;
                }

                throw new RuntimeException(
                    "Merchant #{$lockedSubscription->merchant_id} "
                    . 'has no active subscription.'
                );
            }

            $defaultPlan = $this->getDefaultPlan();

            /*
             * If the merchant is already on the default plan,
             * there is nothing to switch.
             */
            if (
                (int) $lockedSubscription->billing_plan_id
                === (int) $defaultPlan->id
            ) {
                return $lockedSubscription;
            }

            /*
             * The default fallback plan must be per-market.
             */
            $billingType =
                $defaultPlan->billing_type instanceof BillingType
                    ? $defaultPlan->billing_type
                    : BillingType::from($defaultPlan->billing_type);

            if ($billingType !== BillingType::PER_MARKET) {
                throw new RuntimeException(
                    'Default billing plan must use per_market billing.'
                );
            }

            $switchAt = now();

            /*
             * Close the old subscription without destroying
             * its historical billing data.
             */
            $lockedSubscription->update([
                'status' => MerchantSubscriptionStatus::CANCELLED,
                'ends_at' => $switchAt,
            ]);

            /*
             * Close any currently open billing period belonging
             * to the old subscription.
             */
            MerchantBillingPeriod::query()
                ->where(
                    'merchant_subscription_id',
                    $lockedSubscription->id
                )
                ->where('status', MerchantBillingPeriodStatus::OPEN)
                ->where('period_end', '>', $switchAt)
                ->update([
                    'period_end' => $switchAt,
                    'status' => MerchantBillingPeriodStatus::CLOSED,
                ]);

            /*
             * Guarantee that the merchant has a USD wallet.
             *
             * Normally it should already exist because all new
             * merchants are initialized with one.
             */
            MerchantWallet::query()->firstOrCreate(
                [
                    'merchant_id' => $lockedSubscription->merchant_id,
                    'currency' => 'USD',
                ],
                [
                    'balance' => 0,
                ]
            );

            /*
             * Create a new subscription instead of modifying
             * billing_plan_id on the old one.
             *
             * This preserves the complete subscription history.
             */
            return MerchantSubscription::query()->create([
                'merchant_id' => $lockedSubscription->merchant_id,
                'billing_plan_id' => $defaultPlan->id,

                'status' => MerchantSubscriptionStatus::ACTIVE,

                'change_reason' =>
                    MerchantSubscriptionChangeReason::INSUFFICIENT_BALANCE,

                'fallback_from_subscription_id' => $lockedSubscription->id,

                'starts_at' => $switchAt,
                'ends_at' => null,

                'custom_monthly_fee' => null,
                'custom_price_per_market' => null,
                'custom_included_markets' => null,
                'custom_overage_price' => null,
            ]);
        }, 3);
    }


    /**
     * Restore a merchant from the automatic Start fallback
     * to the recurring subscription that existed before it.
     */
    public function restorePreviousPlan(
        MerchantSubscription $fallbackSubscription
    ): MerchantSubscription {
        return DB::transaction(function () use ($fallbackSubscription) {

            $lockedFallback = MerchantSubscription::query()
                ->with('billingPlan')
                ->whereKey($fallbackSubscription->id)
                ->lockForUpdate()
                ->firstOrFail();

            /*
             * Idempotency / concurrency protection.
             */
            if (
                $lockedFallback->status
                !== MerchantSubscriptionStatus::ACTIVE
            ) {
                $activeSubscription = MerchantSubscription::query()
                    ->where(
                        'merchant_id',
                        $lockedFallback->merchant_id
                    )
                    ->where(
                        'status',
                        MerchantSubscriptionStatus::ACTIVE
                    )
                    ->latest('id')
                    ->first();

                if ($activeSubscription) {
                    return $activeSubscription;
                }

                throw new RuntimeException(
                    "Merchant #{$lockedFallback->merchant_id} "
                    . 'has no active subscription.'
                );
            }

            /*
             * Only subscriptions created by the automatic
             * insufficient-balance fallback may be restored.
             */
            if (
                $lockedFallback->change_reason
                !== MerchantSubscriptionChangeReason::INSUFFICIENT_BALANCE
            ) {
                throw new RuntimeException(
                    "Subscription #{$lockedFallback->id} "
                    . 'is not an automatic balance fallback.'
                );
            }

            if (! $lockedFallback->fallback_from_subscription_id) {
                throw new RuntimeException(
                    "Subscription #{$lockedFallback->id} "
                    . 'has no source subscription to restore.'
                );
            }

            $previousSubscription = MerchantSubscription::query()
                ->with('billingPlan')
                ->whereKey(
                    $lockedFallback->fallback_from_subscription_id
                )
                ->first();

            if (! $previousSubscription) {
                throw new RuntimeException(
                    "Source subscription #"
                    . $lockedFallback->fallback_from_subscription_id
                    . ' was not found.'
                );
            }

            if (
                (int) $previousSubscription->merchant_id
                !== (int) $lockedFallback->merchant_id
            ) {
                throw new RuntimeException(
                    'Fallback source belongs to another merchant.'
                );
            }

            if (! $previousSubscription->billingPlan) {
                throw new RuntimeException(
                    "Source subscription #{$previousSubscription->id} "
                    . 'has no billing plan.'
                );
            }

            $billingType =
                $previousSubscription->billingPlan->billing_type
                instanceof BillingType
                    ? $previousSubscription->billingPlan->billing_type
                    : BillingType::from(
                    $previousSubscription->billingPlan->billing_type
                );

            /*
             * There is no reason to "restore" another
             * per-market plan through this mechanism.
             */
            if ($billingType === BillingType::PER_MARKET) {
                throw new RuntimeException(
                    'Fallback source must be a recurring billing plan.'
                );
            }

            $switchAt = now();

            /*
             * Close Start fallback subscription.
             */
            $lockedFallback->update([
                'status' => MerchantSubscriptionStatus::CANCELLED,
                'ends_at' => $switchAt,
            ]);

            /*
             * Normally Start has no billing period, but close one
             * defensively if such a period exists.
             */
            MerchantBillingPeriod::query()
                ->where(
                    'merchant_subscription_id',
                    $lockedFallback->id
                )
                ->where(
                    'status',
                    MerchantBillingPeriodStatus::OPEN
                )
                ->where('period_end', '>', $switchAt)
                ->update([
                    'period_end' => $switchAt,
                    'status' => MerchantBillingPeriodStatus::CLOSED,
                ]);

            /*
             * Create a NEW subscription.
             *
             * Do not reactivate the historical one because that would
             * corrupt the subscription/billing-period timeline.
             *
             * Custom commercial conditions are copied exactly from
             * the subscription that existed before fallback.
             */
            return MerchantSubscription::query()->create([
                'merchant_id' => $lockedFallback->merchant_id,

                'billing_plan_id' =>
                    $previousSubscription->billing_plan_id,

                'status' => MerchantSubscriptionStatus::ACTIVE,

                'change_reason' =>
                    MerchantSubscriptionChangeReason::BALANCE_RESTORED,

                'fallback_from_subscription_id' => null,

                'starts_at' => $switchAt,
                'ends_at' => null,

                'custom_monthly_fee' =>
                    $previousSubscription->custom_monthly_fee,

                'custom_price_per_market' =>
                    $previousSubscription->custom_price_per_market,

                'custom_included_markets' =>
                    $previousSubscription->custom_included_markets,

                'custom_overage_price' =>
                    $previousSubscription->custom_overage_price,
            ]);
        }, 3);
    }

    /**
     * Return the single active default billing plan.
     */
    private function getDefaultPlan(): BillingPlan
    {
        $plans = BillingPlan::query()
            ->where('is_default', true)
            ->where('is_active', true)
            ->limit(2)
            ->get();

        if ($plans->isEmpty()) {
            throw new RuntimeException(
                'No active default billing plan is configured.'
            );
        }

        if ($plans->count() > 1) {
            throw new RuntimeException(
                'More than one active default billing plan is configured.'
            );
        }

        return $plans->first();
    }
}
