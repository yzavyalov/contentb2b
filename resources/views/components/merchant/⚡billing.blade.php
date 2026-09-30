<?php

use App\Models\Merchant;
use App\Services\CurrentMerchant;
use Livewire\Component;

new class extends Component
{
    public ?Merchant $merchant = null;

    public function mount(CurrentMerchant $currentMerchant)
    {
        $this->merchant = $currentMerchant->get();

        /*
         * Billing всегда относится к конкретному merchant.
         * В режиме All Merchants возвращаемся на dashboard.
         */
        if (! $this->merchant) {
            return redirect()->route('merchant.dashboard');
        }
    }

    public function with(): array
    {
        /*
        |--------------------------------------------------------------------------
        | SECURITY
        |--------------------------------------------------------------------------
        |
        | Не доверяем merchant ID, сохранённому в Livewire state.
        | Повторно получаем merchant через authenticated user.
        |
        */

        $merchant = auth()
            ->user()
            ->merchants()
            ->whereKey($this->merchant->id)
            ->firstOrFail();

        /*
        |--------------------------------------------------------------------------
        | WALLET
        |--------------------------------------------------------------------------
        */

        $wallet = $merchant
            ->wallets()
            ->orderBy('id')
            ->first();

        /*
        |--------------------------------------------------------------------------
        | CURRENT SUBSCRIPTION
        |--------------------------------------------------------------------------
        */

        $subscription = $merchant
            ->subscriptions()
            ->with('billingPlan')
            ->where('status', 'active')
            ->latest('starts_at')
            ->first();

        /*
         * На случай старых данных, где active subscription
         * отсутствует, показываем последнюю известную подписку.
         */
        if (! $subscription) {
            $subscription = $merchant
                ->subscriptions()
                ->with('billingPlan')
                ->latest('starts_at')
                ->first();
        }

        $plan = $subscription?->billingPlan;

        /*
        |--------------------------------------------------------------------------
        | EFFECTIVE BILLING VALUES
        |--------------------------------------------------------------------------
        |
        | custom_* имеет приоритет над BillingPlan.
        |
        */

        $monthlyFee = $subscription?->custom_monthly_fee !== null
            ? (float) $subscription->custom_monthly_fee
            : (float) ($plan?->monthly_fee ?? 0);

        $pricePerMarket = $subscription?->custom_price_per_market !== null
            ? (float) $subscription->custom_price_per_market
            : (float) ($plan?->price_per_market ?? 0);

        $includedMarkets = $subscription?->custom_included_markets !== null
            ? (int) $subscription->custom_included_markets
            : (int) ($plan?->included_markets ?? 0);

        $overagePrice = $subscription?->custom_overage_price !== null
            ? (float) $subscription->custom_overage_price
            : (float) ($plan?->overage_price ?? 0);

        /*
        |--------------------------------------------------------------------------
        | CURRENT BILLING PERIOD
        |--------------------------------------------------------------------------
        */

        $currentPeriod = $merchant
            ->billingPeriods()
            ->with('subscription.billingPlan')
            ->where('period_start', '<=', now())
            ->where('period_end', '>=', now())
            ->latest('period_start')
            ->first();

        /*
        |--------------------------------------------------------------------------
        | TRANSACTIONS
        |--------------------------------------------------------------------------
        */

        $transactions = $merchant
            ->balanceTransactions()
            ->latest('id')
            ->limit(50)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | BILLING PERIOD HISTORY
        |--------------------------------------------------------------------------
        */

        $billingPeriods = $merchant
            ->billingPeriods()
            ->with('subscription.billingPlan')
            ->latest('period_start')
            ->limit(12)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | USAGE
        |--------------------------------------------------------------------------
        */

        $marketsUsed = (int) ($currentPeriod?->markets_used ?? 0);

        $periodIncludedMarkets = (int) (
            $currentPeriod?->included_markets
            ?? $includedMarkets
        );

        $remainingIncluded = max(
            0,
            $periodIncludedMarkets - $marketsUsed
        );

        /*
         * Сумма фактических расходов текущего периода.
         *
         * Берём MerchantBet, которые действительно были charged
         * в границах текущего billing period.
         */

        $periodCost = 0;

        if ($currentPeriod) {
            $periodCost = (float) $merchant
                ->merchantBets()
                ->whereNotNull('charged_at')
                ->whereBetween(
                    'charged_at',
                    [
                        $currentPeriod->period_start,
                        $currentPeriod->period_end,
                    ]
                )
                ->sum('charged_amount');

            /*
             * Monthly fee хранится отдельно от MerchantBet.
             */
            if ($currentPeriod->monthly_fee_charged_at) {
                $periodCost += (float) $currentPeriod->monthly_fee;
            }
        }

        return compact(
            'merchant',
            'wallet',
            'subscription',
            'plan',
            'currentPeriod',
            'transactions',
            'billingPeriods',
            'monthlyFee',
            'pricePerMarket',
            'includedMarkets',
            'overagePrice',
            'marketsUsed',
            'periodIncludedMarkets',
            'remainingIncluded',
            'periodCost'
        );
    }
};
?>

<div class="space-y-7">

    {{-- HEADER --}}
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <div class="text-xs font-black uppercase tracking-[0.18em] text-lime-500">
                Billing
            </div>

            <h1 class="mt-2 text-3xl font-black text-[var(--wr-text)]">
                Billing & Usage
            </h1>

            <p class="mt-2 text-sm text-[var(--wr-muted)]">
                Billing information for {{ $merchant->name }}.
            </p>
        </div>

        <div class="rounded-2xl border border-[var(--wr-border)] bg-[var(--wr-panel)] px-5 py-3">
            <div class="text-[10px] font-black uppercase tracking-[0.14em] text-[var(--wr-muted)]">
                Merchant ID
            </div>

            <div class="mt-1 text-lg font-black text-[var(--wr-text)]">
                #{{ $merchant->id }}
            </div>
        </div>
    </div>


    {{-- SUMMARY --}}
    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">

        <div class="rounded-2xl border border-[var(--wr-border)] bg-[var(--wr-panel)] p-6">
            <div class="text-xs font-black uppercase tracking-[0.12em] text-[var(--wr-muted)]">
                Current Balance
            </div>

            <div class="mt-3 text-3xl font-black text-[var(--wr-text)]">
                {{ $wallet?->currency ?? 'USD' }}
                {{ number_format((float) ($wallet?->balance ?? 0), 2) }}
            </div>
        </div>


        <div class="rounded-2xl border border-[var(--wr-border)] bg-[var(--wr-panel)] p-6">
            <div class="text-xs font-black uppercase tracking-[0.12em] text-[var(--wr-muted)]">
                Current Plan
            </div>

            <div class="mt-3 text-2xl font-black text-[var(--wr-text)]">
                {{ $plan?->name ?? 'No plan' }}
            </div>

            <div class="mt-1 text-sm text-[var(--wr-muted)]">
                {{ $plan?->billing_type?->label() ?? '—' }}
            </div>
        </div>


        <div class="rounded-2xl border border-[var(--wr-border)] bg-[var(--wr-panel)] p-6">
            <div class="text-xs font-black uppercase tracking-[0.12em] text-[var(--wr-muted)]">
                Markets Used
            </div>

            <div class="mt-3 text-3xl font-black text-[var(--wr-text)]">
                {{ number_format($marketsUsed) }}
            </div>

            @if($periodIncludedMarkets > 0)
                <div class="mt-1 text-sm text-[var(--wr-muted)]">
                    {{ number_format($remainingIncluded) }}
                    included remaining
                </div>
            @endif
        </div>


        <div class="rounded-2xl border border-[var(--wr-border)] bg-[var(--wr-panel)] p-6">
            <div class="text-xs font-black uppercase tracking-[0.12em] text-[var(--wr-muted)]">
                Current Period Cost
            </div>

            <div class="mt-3 text-3xl font-black text-[var(--wr-text)]">
                {{ $wallet?->currency ?? 'USD' }}
                {{ number_format($periodCost, 2) }}
            </div>
        </div>

    </div>


    <div class="grid gap-6 xl:grid-cols-2">

        {{-- PLAN --}}
        <div class="rounded-2xl border border-[var(--wr-border)] bg-[var(--wr-panel)] p-6">

            <h2 class="text-xl font-black text-[var(--wr-text)]">
                Current Plan
            </h2>

            @if($subscription && $plan)

                <div class="mt-6 space-y-4">

                    <div class="flex items-center justify-between gap-4 border-b border-[var(--wr-border)] pb-4">
                        <span class="text-sm text-[var(--wr-muted)]">
                            Plan
                        </span>

                        <span class="font-black text-[var(--wr-text)]">
                            {{ $plan->name }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between gap-4 border-b border-[var(--wr-border)] pb-4">
                        <span class="text-sm text-[var(--wr-muted)]">
                            Billing type
                        </span>

                        <span class="font-black text-[var(--wr-text)]">
                            {{ $plan->billing_type?->label() ?? '—' }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between gap-4 border-b border-[var(--wr-border)] pb-4">
                        <span class="text-sm text-[var(--wr-muted)]">
                            Monthly fee
                        </span>

                        <span class="font-black text-[var(--wr-text)]">
                            ${{ number_format($monthlyFee, 2) }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between gap-4 border-b border-[var(--wr-border)] pb-4">
                        <span class="text-sm text-[var(--wr-muted)]">
                            Price per market
                        </span>

                        <span class="font-black text-[var(--wr-text)]">
                            ${{ number_format($pricePerMarket, 2) }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between gap-4 border-b border-[var(--wr-border)] pb-4">
                        <span class="text-sm text-[var(--wr-muted)]">
                            Included markets
                        </span>

                        <span class="font-black text-[var(--wr-text)]">
                            @if($plan->is_unlimited)
                                Unlimited
                            @else
                                {{ number_format($includedMarkets) }}
                            @endif
                        </span>
                    </div>

                    <div class="flex items-center justify-between gap-4">
                        <span class="text-sm text-[var(--wr-muted)]">
                            Overage price
                        </span>

                        <span class="font-black text-[var(--wr-text)]">
                            @if($plan->is_unlimited)
                                —
                            @else
                                ${{ number_format($overagePrice, 2) }}
                            @endif
                        </span>
                    </div>

                </div>

            @else

                <div class="mt-6 rounded-xl border border-[var(--wr-border)] p-5 text-sm text-[var(--wr-muted)]">
                    No billing plan is currently assigned to this merchant.
                </div>

            @endif
        </div>


        {{-- CURRENT PERIOD --}}
        <div class="rounded-2xl border border-[var(--wr-border)] bg-[var(--wr-panel)] p-6">

            <h2 class="text-xl font-black text-[var(--wr-text)]">
                Current Billing Period
            </h2>

            @if($currentPeriod)

                <div class="mt-6 space-y-4">

                    <div class="flex justify-between gap-4 border-b border-[var(--wr-border)] pb-4">
                        <span class="text-sm text-[var(--wr-muted)]">
                            Period
                        </span>

                        <span class="text-right font-black text-[var(--wr-text)]">
                            {{ $currentPeriod->period_start?->format('M d, Y') }}
                            -
                            {{ $currentPeriod->period_end?->format('M d, Y') }}
                        </span>
                    </div>

                    <div class="flex justify-between gap-4 border-b border-[var(--wr-border)] pb-4">
                        <span class="text-sm text-[var(--wr-muted)]">
                            Markets used
                        </span>

                        <span class="font-black text-[var(--wr-text)]">
                            {{ number_format($marketsUsed) }}
                        </span>
                    </div>

                    <div class="flex justify-between gap-4 border-b border-[var(--wr-border)] pb-4">
                        <span class="text-sm text-[var(--wr-muted)]">
                            Included markets
                        </span>

                        <span class="font-black text-[var(--wr-text)]">
                            {{ number_format($periodIncludedMarkets) }}
                        </span>
                    </div>

                    <div class="flex justify-between gap-4 border-b border-[var(--wr-border)] pb-4">
                        <span class="text-sm text-[var(--wr-muted)]">
                            Monthly fee
                        </span>

                        <span class="font-black text-[var(--wr-text)]">
                            ${{ number_format((float) $currentPeriod->monthly_fee, 2) }}
                        </span>
                    </div>

                    <div class="flex justify-between gap-4 border-b border-[var(--wr-border)] pb-4">
                        <span class="text-sm text-[var(--wr-muted)]">
                            Overage
                        </span>

                        <span class="font-black text-[var(--wr-text)]">
                            ${{ number_format((float) $currentPeriod->overage_amount, 2) }}
                        </span>
                    </div>

                    <div class="flex justify-between gap-4">
                        <span class="text-sm text-[var(--wr-muted)]">
                            Status
                        </span>

                        <span class="font-black text-[var(--wr-text)]">
                            {{ ucfirst($currentPeriod->status->value) }}
                        </span>
                    </div>

                </div>

            @else

                <div class="mt-6 rounded-xl border border-[var(--wr-border)] p-5 text-sm text-[var(--wr-muted)]">
                    There is no active billing period.
                </div>

            @endif

        </div>

    </div>


    {{-- TRANSACTIONS --}}
    <div class="overflow-hidden rounded-2xl border border-[var(--wr-border)] bg-[var(--wr-panel)]">

        <div class="border-b border-[var(--wr-border)] p-6">
            <h2 class="text-xl font-black text-[var(--wr-text)]">
                Balance History
            </h2>

            <p class="mt-1 text-sm text-[var(--wr-muted)]">
                Latest credits and charges for this merchant.
            </p>
        </div>

        @if($transactions->isEmpty())

            <div class="p-8 text-center text-sm text-[var(--wr-muted)]">
                No balance transactions yet.
            </div>

        @else

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">

                    <thead>
                    <tr class="border-b border-[var(--wr-border)] text-left text-[10px] font-black uppercase tracking-[0.12em] text-[var(--wr-muted)]">
                        <th class="px-6 py-4">Date</th>
                        <th class="px-6 py-4">Type</th>
                        <th class="px-6 py-4">Description</th>
                        <th class="px-6 py-4 text-right">Amount</th>
                        <th class="px-6 py-4 text-right">Balance</th>
                    </tr>
                    </thead>

                    <tbody>

                    @foreach($transactions as $transaction)
                        <tr class="border-b border-[var(--wr-border)] last:border-b-0">

                            <td class="whitespace-nowrap px-6 py-4 text-[var(--wr-muted)]">
                                {{ $transaction->created_at?->format('Y-m-d H:i') }}
                            </td>

                            <td class="px-6 py-4 font-bold text-[var(--wr-text)]">
                                {{ ucfirst($transaction->type->value) }}
                            </td>

                            <td class="px-6 py-4 text-[var(--wr-text-soft)]">
                                {{ $transaction->description ?: '—' }}
                            </td>

                            <td class="whitespace-nowrap px-6 py-4 text-right font-black text-[var(--wr-text)]">
                                {{ $transaction->currency }}
                                {{ number_format((float) $transaction->amount, 2) }}
                            </td>

                            <td class="whitespace-nowrap px-6 py-4 text-right text-[var(--wr-text)]">
                                {{ $transaction->currency }}
                                {{ number_format((float) $transaction->balance_after, 2) }}
                            </td>

                        </tr>
                    @endforeach

                    </tbody>
                </table>
            </div>

        @endif

    </div>


    {{-- PERIOD HISTORY --}}
    <div class="overflow-hidden rounded-2xl border border-[var(--wr-border)] bg-[var(--wr-panel)]">

        <div class="border-b border-[var(--wr-border)] p-6">
            <h2 class="text-xl font-black text-[var(--wr-text)]">
                Billing Periods
            </h2>

            <p class="mt-1 text-sm text-[var(--wr-muted)]">
                Recent billing periods and usage.
            </p>
        </div>

        @if($billingPeriods->isEmpty())

            <div class="p-8 text-center text-sm text-[var(--wr-muted)]">
                No billing periods yet.
            </div>

        @else

            <div class="overflow-x-auto">

                <table class="min-w-full text-sm">

                    <thead>
                    <tr class="border-b border-[var(--wr-border)] text-left text-[10px] font-black uppercase tracking-[0.12em] text-[var(--wr-muted)]">
                        <th class="px-6 py-4">Period</th>
                        <th class="px-6 py-4">Plan</th>
                        <th class="px-6 py-4 text-right">Usage</th>
                        <th class="px-6 py-4 text-right">Monthly Fee</th>
                        <th class="px-6 py-4 text-right">Overage</th>
                        <th class="px-6 py-4">Status</th>
                    </tr>
                    </thead>

                    <tbody>

                    @foreach($billingPeriods as $period)

                        <tr class="border-b border-[var(--wr-border)] last:border-b-0">

                            <td class="whitespace-nowrap px-6 py-4 text-[var(--wr-text)]">
                                {{ $period->period_start?->format('Y-m-d') }}
                                -
                                {{ $period->period_end?->format('Y-m-d') }}
                            </td>

                            <td class="px-6 py-4 font-bold text-[var(--wr-text)]">
                                {{ $period->subscription?->billingPlan?->name ?? '—' }}
                            </td>

                            <td class="px-6 py-4 text-right text-[var(--wr-text)]">
                                {{ number_format((int) $period->markets_used) }}

                                @if((int) $period->included_markets > 0)
                                    /
                                    {{ number_format((int) $period->included_markets) }}
                                @endif
                            </td>

                            <td class="px-6 py-4 text-right text-[var(--wr-text)]">
                                ${{ number_format((float) $period->monthly_fee, 2) }}
                            </td>

                            <td class="px-6 py-4 text-right text-[var(--wr-text)]">
                                ${{ number_format((float) $period->overage_amount, 2) }}
                            </td>

                            <td class="px-6 py-4 font-bold text-[var(--wr-text)]">
                                {{ ucfirst($period->status->value) }}
                            </td>

                        </tr>

                    @endforeach

                    </tbody>
                </table>

            </div>

        @endif

    </div>

</div>
