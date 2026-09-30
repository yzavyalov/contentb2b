<?php

namespace App\Console\Commands;

use App\Enums\BillingType;
use App\Enums\MerchantSubscriptionChangeReason;
use App\Enums\MerchantSubscriptionStatus;
use App\Models\MerchantSubscription;
use App\Models\MerchantWallet;
use App\Services\MerchantBillingPeriodService;
use App\Services\MerchantSubscriptionService;
use App\Services\MonthlySubscriptionFeeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class ProcessMerchantBilling extends Command
{
    protected $signature = 'billing:process';

    protected $description =
        'Create merchant billing periods, process subscription fees and handle automatic fallback/restore';

    public function handle(
        MerchantBillingPeriodService $billingPeriodService,
        MonthlySubscriptionFeeService $monthlyFeeService,
        MerchantSubscriptionService $subscriptionService
    ): int {
        $processed = 0;
        $charged = 0;
        $fallbacks = 0;
        $restores = 0;
        $failed = 0;

        MerchantSubscription::query()
            ->with([
                'merchant',
                'billingPlan',
            ])
            ->where('status', MerchantSubscriptionStatus::ACTIVE)
            ->where(function ($query) {
                $query
                    ->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            })
            ->where(function ($query) {
                $query
                    ->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', now());
            })
            ->orderBy('id')
            ->chunkById(
                100,
                function ($subscriptions) use (
                    $billingPeriodService,
                    $monthlyFeeService,
                    $subscriptionService,
                    &$processed,
                    &$charged,
                    &$fallbacks,
                    &$restores,
                    &$failed
                ) {
                    foreach ($subscriptions as $subscription) {
                        try {
                            if (! $subscription->merchant) {
                                throw new RuntimeException(
                                    "Subscription #{$subscription->id} has no merchant."
                                );
                            }

                            if (! $subscription->billingPlan) {
                                throw new RuntimeException(
                                    "Subscription #{$subscription->id} has no billing plan."
                                );
                            }

                            $plan = $subscription->billingPlan;

                            $billingType =
                                $plan->billing_type instanceof BillingType
                                    ? $plan->billing_type
                                    : BillingType::from($plan->billing_type);

                            /*
                             * -------------------------------------------------
                             * AUTOMATIC PLAN RESTORE
                             * -------------------------------------------------
                             *
                             * A PER_MARKET subscription may actually be the
                             * temporary Start fallback created because the
                             * merchant could not pay a recurring monthly fee.
                             *
                             * Once sufficient balance becomes available again,
                             * restore the recurring subscription that existed
                             * before the fallback.
                             */
                            if (
                                $billingType === BillingType::PER_MARKET
                                && $subscription->change_reason
                                === MerchantSubscriptionChangeReason::INSUFFICIENT_BALANCE
                                && $subscription->fallback_from_subscription_id
                            ) {
                                $previousSubscription =
                                    MerchantSubscription::query()
                                        ->with('billingPlan')
                                        ->find(
                                            $subscription
                                                ->fallback_from_subscription_id
                                        );

                                if (
                                    ! $previousSubscription
                                    || ! $previousSubscription->billingPlan
                                ) {
                                    throw new RuntimeException(
                                        "Subscription #{$subscription->id}: "
                                        . 'fallback source subscription or '
                                        . 'billing plan was not found.'
                                    );
                                }

                                if (
                                    (int) $previousSubscription->merchant_id
                                    !== (int) $subscription->merchant_id
                                ) {
                                    throw new RuntimeException(
                                        "Subscription #{$subscription->id}: "
                                        . 'fallback source belongs to another merchant.'
                                    );
                                }

                                $previousPlan =
                                    $previousSubscription->billingPlan;

                                $previousBillingType =
                                    $previousPlan->billing_type
                                    instanceof BillingType
                                        ? $previousPlan->billing_type
                                        : BillingType::from(
                                        $previousPlan->billing_type
                                    );

                                /*
                                 * Only recurring plans are restored.
                                 *
                                 * A normal PER_MARKET plan does not have a
                                 * recurring monthly subscription fee and does
                                 * not belong to this restore mechanism.
                                 */
                                if (
                                    $previousBillingType
                                    !== BillingType::PER_MARKET
                                ) {
                                    $requiredMonthlyFee = round(
                                        (float) (
                                            $previousSubscription
                                                ->custom_monthly_fee
                                            ?? $previousPlan->monthly_fee
                                            ?? 0
                                        ),
                                        2
                                    );

                                    /*
                                     * This is only a preliminary balance check.
                                     *
                                     * The final balance validation is performed
                                     * again by MonthlySubscriptionFeeService
                                     * while the USD wallet is locked.
                                     */
                                    if (
                                        $requiredMonthlyFee <= 0
                                        || $this->hasEnoughBalance(
                                            $subscription->merchant_id,
                                            $requiredMonthlyFee
                                        )
                                    ) {
                                        /*
                                         * Restore, billing-period creation and
                                         * monthly fee charge must succeed as one
                                         * atomic operation.
                                         *
                                         * If anything throws below, the outer
                                         * transaction rolls everything back:
                                         *
                                         * - fallback remains active;
                                         * - restored subscription is not kept;
                                         * - billing period is not kept;
                                         * - wallet balance is unchanged;
                                         * - no charge transaction is kept.
                                         */
                                        $restoreResult = DB::transaction(
                                            function () use (
                                                $subscription,
                                                $subscriptionService,
                                                $billingPeriodService,
                                                $monthlyFeeService
                                            ) {
                                                $restoredSubscription =
                                                    $subscriptionService
                                                        ->restorePreviousPlan(
                                                            $subscription
                                                        );

                                                $restoredSubscription->load([
                                                    'merchant',
                                                    'billingPlan',
                                                ]);

                                                if (
                                                    ! $restoredSubscription
                                                        ->merchant
                                                ) {
                                                    throw new RuntimeException(
                                                        "Restored subscription "
                                                        . "#{$restoredSubscription->id} "
                                                        . 'has no merchant.'
                                                    );
                                                }

                                                if (
                                                    ! $restoredSubscription
                                                        ->billingPlan
                                                ) {
                                                    throw new RuntimeException(
                                                        "Restored subscription "
                                                        . "#{$restoredSubscription->id} "
                                                        . 'has no billing plan.'
                                                    );
                                                }

                                                $restoredPlan =
                                                    $restoredSubscription
                                                        ->billingPlan;

                                                $restoredBillingType =
                                                    $restoredPlan->billing_type
                                                    instanceof BillingType
                                                        ? $restoredPlan
                                                        ->billing_type
                                                        : BillingType::from(
                                                        $restoredPlan
                                                            ->billing_type
                                                    );

                                                if (
                                                    $restoredBillingType
                                                    === BillingType::PER_MARKET
                                                ) {
                                                    throw new RuntimeException(
                                                        "Restored subscription "
                                                        . "#{$restoredSubscription->id} "
                                                        . 'must use recurring billing.'
                                                    );
                                                }

                                                $includedMarkets = (int) (
                                                    $restoredSubscription
                                                        ->custom_included_markets
                                                    ?? $restoredPlan
                                                    ->included_markets
                                                    ?? 0
                                                );

                                                /*
                                                 * Create the first billing
                                                 * period for the newly restored
                                                 * subscription.
                                                 */
                                                $restoredPeriod =
                                                    $billingPeriodService
                                                        ->getOrCreateCurrentPeriod(
                                                            $restoredSubscription
                                                                ->merchant,
                                                            $restoredSubscription,
                                                            $includedMarkets
                                                        );

                                                /*
                                                 * MonthlySubscriptionFeeService
                                                 * performs the authoritative
                                                 * balance check while holding
                                                 * the wallet lock.
                                                 *
                                                 * Its DB transaction is nested
                                                 * inside this outer transaction.
                                                 */
                                                $monthlyFeeService->charge(
                                                    $restoredPeriod
                                                );

                                                $restoredPeriod->refresh();

                                                if (
                                                    $restoredPeriod
                                                        ->monthly_fee_charged_at
                                                    === null
                                                ) {
                                                    throw new RuntimeException(
                                                        "Restored subscription "
                                                        . "#{$restoredSubscription->id} "
                                                        . 'was not charged successfully.'
                                                    );
                                                }

                                                return [
                                                    'subscription_id' =>
                                                        $restoredSubscription->id,

                                                    'plan_name' =>
                                                        $restoredPlan->name,

                                                    'period_id' =>
                                                        $restoredPeriod->id,

                                                    'monthly_fee' =>
                                                        round(
                                                            (float)
                                                            $restoredPeriod
                                                                ->monthly_fee,
                                                            2
                                                        ),
                                                ];
                                            },
                                            3
                                        );

                                        /*
                                         * Counters are changed only AFTER the
                                         * transaction has committed.
                                         */
                                        $restores++;
                                        $processed++;

                                        if (
                                            $restoreResult['monthly_fee'] > 0
                                        ) {
                                            $charged++;
                                        }

                                        $this->info(
                                            "Subscription #{$subscription->id}: "
                                            . "Merchant #{$subscription->merchant_id} "
                                            . 'automatically restored to '
                                            . "{$restoreResult['plan_name']} "
                                            . '(subscription #'
                                            . "{$restoreResult['subscription_id']}, "
                                            . 'period #'
                                            . "{$restoreResult['period_id']})."
                                        );

                                        continue;
                                    }
                                }
                            }

                            /*
                             * -------------------------------------------------
                             * NORMAL PER-MARKET PLAN
                             * -------------------------------------------------
                             *
                             * There is no recurring monthly subscription fee.
                             */
                            if ($billingType === BillingType::PER_MARKET) {
                                continue;
                            }

                            /*
                             * -------------------------------------------------
                             * NORMAL RECURRING BILLING
                             * -------------------------------------------------
                             */
                            $includedMarkets = (int) (
                                $subscription->custom_included_markets
                                ?? $plan->included_markets
                                ?? 0
                            );

                            /*
                             * Find or create the current billing period.
                             */
                            $period = $billingPeriodService
                                ->getOrCreateCurrentPeriod(
                                    $subscription->merchant,
                                    $subscription,
                                    $includedMarkets
                                );

                            $processed++;

                            /*
                             * If this billing period has already been charged,
                             * MonthlySubscriptionFeeService is idempotent and
                             * will return without another wallet transaction.
                             */
                            $wasAlreadyCharged =
                                $period->monthly_fee_charged_at !== null;

                            /*
                             * Use the monthly fee stored on the billing period.
                             *
                             * The period is the billing snapshot and therefore
                             * is the correct source for the current charge.
                             */
                            $monthlyFee = round(
                                (float) $period->monthly_fee,
                                2
                            );

                            /*
                             * If the recurring fee has not been paid and the
                             * merchant currently has insufficient balance,
                             * automatically move the merchant to Start.
                             *
                             * Zero-fee recurring plans never need fallback.
                             */
                            if (
                                ! $wasAlreadyCharged
                                && $monthlyFee > 0
                                && ! $this->hasEnoughBalance(
                                    $subscription->merchant_id,
                                    $monthlyFee
                                )
                            ) {
                                $fallbackSubscription =
                                    $subscriptionService
                                        ->fallbackToDefaultPlan(
                                            $subscription
                                        );

                                $fallbacks++;

                                $this->warn(
                                    "Subscription #{$subscription->id}: "
                                    . 'insufficient USD balance. '
                                    . "Merchant #{$subscription->merchant_id} "
                                    . 'automatically switched to '
                                    . 'default plan '
                                    . "(subscription #{$fallbackSubscription->id})."
                                );

                                continue;
                            }

                            /*
                             * Process recurring monthly fee.
                             *
                             * MonthlySubscriptionFeeService remains the final
                             * authority:
                             *
                             * - locks the billing period;
                             * - prevents duplicate charges;
                             * - locks the USD wallet;
                             * - validates balance;
                             * - updates wallet;
                             * - creates ledger transaction;
                             * - marks the billing period as charged.
                             */
                            $monthlyFeeService->charge($period);

                            $period->refresh();

                            if (
                                ! $wasAlreadyCharged
                                && $period->monthly_fee_charged_at !== null
                            ) {
                                $charged++;
                            }

                            $this->line(
                                "Subscription #{$subscription->id}: "
                                . "period #{$period->id} processed."
                            );
                        } catch (Throwable $e) {
                            $failed++;

                            $this->error(
                                "Subscription #{$subscription->id}: "
                                . $e->getMessage()
                            );

                            report($e);
                        }
                    }
                }
            );

        $this->newLine();

        $this->info(
            'Billing completed. '
            . "Periods processed: {$processed}; "
            . "fees processed: {$charged}; "
            . "fallbacks: {$fallbacks}; "
            . "restores: {$restores}; "
            . "failed: {$failed}."
        );

        return $failed > 0
            ? self::FAILURE
            : self::SUCCESS;
    }

    /**
     * Preliminary check whether the merchant currently has
     * enough USD to pay the recurring monthly fee.
     *
     * This check is intentionally NOT considered authoritative.
     *
     * MonthlySubscriptionFeeService performs the final balance
     * validation while holding a lock on the USD wallet.
     *
     * Missing wallet is treated as zero balance.
     */
    private function hasEnoughBalance(
        int $merchantId,
        float $requiredAmount
    ): bool {
        $balance = MerchantWallet::query()
            ->where('merchant_id', $merchantId)
            ->where('currency', 'USD')
            ->value('balance');

        if ($balance === null) {
            return false;
        }

        return round((float) $balance, 2)
            >= round($requiredAmount, 2);
    }
}
