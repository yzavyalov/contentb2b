<?php

namespace App\Services;

use App\Enums\BillingType;
use App\Enums\MerchantBalanceTransactionType;
use App\Enums\MerchantSubscriptionStatus;
use App\Models\Merchant;
use App\Models\MerchantBalanceTransaction;
use App\Models\MerchantBet;
use App\Models\MerchantSubscription;
use App\Models\MerchantWallet;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MerchantBillingService
{
    public function __construct(
        private readonly MerchantBillingPeriodService $billingPeriodService
    ) {
    }

    /**
     * Process billing for the initial delivery of a market to a merchant.
     *
     * The method is idempotent:
     * if charged_at is already set, the market will not be billed again.
     */
    public function chargeForMarket(
        Merchant $merchant,
        MerchantBet $merchantBet
    ): void {
        DB::transaction(function () use ($merchant, $merchantBet) {

            /*
             * Lock MerchantBet so two workers cannot bill
             * the same delivery simultaneously.
             */
            $lockedBet = MerchantBet::query()
                ->whereKey($merchantBet->id)
                ->lockForUpdate()
                ->firstOrFail();

            /*
             * Billing for this initial delivery has already
             * been processed.
             *
             * charged_at is also set for zero-cost markets.
             */
            if ($lockedBet->charged_at !== null) {
                return;
            }

            /*
             * Find the merchant's currently active subscription.
             */
            $subscription = MerchantSubscription::query()
                ->with('billingPlan')
                ->where('merchant_id', $merchant->id)
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
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if (! $subscription || ! $subscription->billingPlan) {
                throw new RuntimeException(
                    "Merchant #{$merchant->id} has no active billing plan."
                );
            }

            $plan = $subscription->billingPlan;

            /*
             * billing_type may already be cast to BillingType.
             */
            $billingType = $plan->billing_type instanceof BillingType
                ? $plan->billing_type
                : BillingType::from($plan->billing_type);

            /*
             * Subscription-level custom values override
             * values from the billing plan.
             */
            $pricePerMarket = $this->money(
                $subscription->custom_price_per_market
                ?? $plan->price_per_market
                ?? 0
            );

            $includedMarkets = (int) (
                $subscription->custom_included_markets
                ?? $plan->included_markets
                ?? 0
            );

            $overagePrice = $this->money(
                $subscription->custom_overage_price
                ?? $plan->overage_price
                ?? 0
            );

            $isUnlimited = (bool) $plan->is_unlimited;

            $chargeAmount = 0.00;
            $billingMode = $billingType->value;

            /*
             * PER MARKET
             *
             * Every successfully delivered market is charged
             * using price_per_market.
             */
            if ($billingType === BillingType::PER_MARKET) {
                $chargeAmount = $pricePerMarket;
            } else {

                /*
                 * SUBSCRIPTION / HYBRID
                 *
                 * Both modes use a billing period to track
                 * included markets and overage usage.
                 *
                 * Period creation/search is handled by the
                 * dedicated MerchantBillingPeriodService.
                 */
                $period = $this->billingPeriodService
                    ->getOrCreateCurrentPeriod(
                        $merchant,
                        $subscription,
                        $includedMarkets
                    );

                $usedBefore = (int) $period->markets_used;

                /*
                 * This delivery consumes one market from
                 * the current billing period.
                 */
                $period->markets_used = $usedBefore + 1;

                /*
                 * Unlimited plans never generate overage.
                 *
                 * For limited plans, overage begins after
                 * all included markets have been consumed.
                 *
                 * Example:
                 *
                 * included_markets = 2
                 *
                 * market #1 -> usedBefore 0 -> free
                 * market #2 -> usedBefore 1 -> free
                 * market #3 -> usedBefore 2 -> overage
                 */
                if (
                    ! $isUnlimited
                    && $usedBefore >= (int) $period->included_markets
                ) {
                    $chargeAmount = $overagePrice;

                    $period->overage_amount = $this->money(
                        (float) $period->overage_amount
                        + $chargeAmount
                    );
                }

                $period->save();
            }

            /*
             * Do not create financial transactions for
             * zero-cost included/unlimited markets.
             */
            if ($chargeAmount > 0) {
                $this->debitWallet(
                    merchant: $merchant,
                    merchantBet: $lockedBet,
                    amount: $chargeAmount
                );
            }

            /*
             * charged_at means billing processing for this
             * initial market delivery has completed.
             *
             * It is intentionally populated even when
             * chargeAmount is zero.
             */
            $lockedBet->update([
                'billing_mode' => $billingMode,
                'unit_price' => $chargeAmount,
                'charged_amount' => $chargeAmount,
                'charged_at' => now(),
            ]);
        }, 3);
    }

    /**
     * Debit merchant wallet and create the corresponding
     * financial ledger transaction.
     */
    private function debitWallet(
        Merchant $merchant,
        MerchantBet $merchantBet,
        float $amount
    ): void {
        /*
         * Current billing currency.
         *
         * When multi-currency billing is introduced,
         * currency should come from the billing plan or
         * merchant subscription instead.
         */
        $currency = 'USD';

        /*
         * Lock wallet to prevent concurrent balance changes
         * from producing an incorrect balance.
         */
        $wallet = MerchantWallet::query()
            ->where('merchant_id', $merchant->id)
            ->where('currency', $currency)
            ->lockForUpdate()
            ->first();

        if (! $wallet) {
            throw new RuntimeException(
                "Merchant #{$merchant->id} has no {$currency} wallet."
            );
        }

        $before = $this->money($wallet->balance);
        $after = $this->money($before - $amount);

        /*
         * Negative merchant balance is currently prohibited.
         */
        if ($after < 0) {
            throw new RuntimeException(
                "Merchant #{$merchant->id} has insufficient {$currency} balance."
            );
        }

        $wallet->update([
            'balance' => $after,
        ]);

        /*
         * Record the charge in the immutable financial ledger.
         */
        MerchantBalanceTransaction::query()->create([
            'merchant_id' => $merchant->id,

            'type' => MerchantBalanceTransactionType::MARKET_CHARGE,

            'amount' => $amount,

            'balance_before' => $before,
            'balance_after' => $after,

            'currency' => $currency,

            'reference_type' => MerchantBet::class,
            'reference_id' => $merchantBet->id,

            'description' =>
                "Market #{$merchantBet->bet_id} initial delivery charge.",

            /*
             * null means the transaction was created
             * automatically by the system.
             */
            'created_by_user_id' => null,
        ]);
    }

    /**
     * Normalize monetary values to two decimal places.
     */
    private function money(mixed $value): float
    {
        return round((float) $value, 2);
    }
}
