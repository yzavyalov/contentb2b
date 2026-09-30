@extends('dashboards.layouts.dashboard')

@php
    /*
    |--------------------------------------------------------------------------
    | MERCHANT DASHBOARD CONTEXT
    |--------------------------------------------------------------------------
    |
    | No current merchant = "All Merchants" account-level dashboard.
    | Current merchant selected = dashboard scoped to that merchant only.
    |
    */

    $user = auth()->user();

    $selectedMerchant = $currentMerchant ?? app(\App\Services\CurrentMerchant::class)->get($user);
    $isAllMerchants = $selectedMerchant === null;

    /*
    |--------------------------------------------------------------------------
    | ALL MERCHANT ACCOUNTS
    |--------------------------------------------------------------------------
    */

    $merchants = $user->merchants()
        ->with([
            'wallets',
            'subscriptions' => function ($query) {
                $query
                    ->with('billingPlan')
                    ->where('status', 'active')
                    ->latest('starts_at');
            },
        ])
        ->orderBy('name')
        ->orderBy('id')
        ->get();

    /*
    |--------------------------------------------------------------------------
    | DASHBOARD SCOPE
    |--------------------------------------------------------------------------
    |
    | All mode: every merchant owned by the user.
    | Selected mode: only the selected merchant.
    |
    */

    $dashboardMerchants = $isAllMerchants
        ? $merchants
        : $merchants->where('id', $selectedMerchant->id)->values();

    $dashboardMerchantIds = $dashboardMerchants->pluck('id');

    /*
    |--------------------------------------------------------------------------
    | BALANCE
    |--------------------------------------------------------------------------
    */

    $totalBalance = $dashboardMerchants->sum(function ($merchant) {
        return (float) (
            $merchant->wallets
                ->firstWhere('currency', 'USD')
                ?->balance ?? 0
        );
    });

    /*
    |--------------------------------------------------------------------------
    | MARKETS DELIVERED
    |--------------------------------------------------------------------------
    */

    $marketsDelivered = $dashboardMerchantIds->isNotEmpty()
        ? \App\Models\MerchantBet::query()
            ->whereIn('merchant_id', $dashboardMerchantIds)
            ->whereNotNull('delivered_at')
            ->count()
        : 0;

    /*
    |--------------------------------------------------------------------------
    | THIS MONTH COST
    |--------------------------------------------------------------------------
    */

    $monthCost = $dashboardMerchantIds->isNotEmpty()
        ? abs((float) \App\Models\MerchantBalanceTransaction::query()
            ->whereIn('merchant_id', $dashboardMerchantIds)
            ->whereIn('type', [
                'market_charge',
                'subscription_charge',
            ])
            ->whereBetween('created_at', [
                now()->startOfMonth(),
                now()->endOfMonth(),
            ])
            ->sum('amount'))
        : 0;

    /*
    |--------------------------------------------------------------------------
    | ACTIVE AUTO RULES
    |--------------------------------------------------------------------------
    */

    $activeAutoRules = $dashboardMerchantIds->isNotEmpty()
        ? \App\Models\MerchantDeliveryRule::query()
            ->whereIn('merchant_id', $dashboardMerchantIds)
            ->where('is_active', true)
            ->count()
        : 0;

    /*
    |--------------------------------------------------------------------------
    | RECENT DELIVERIES
    |--------------------------------------------------------------------------
    |
    | All mode: deliveries from all owned merchants.
    | Selected mode: deliveries from the selected merchant only.
    |
    */

    $recentDeliveries = $dashboardMerchantIds->isNotEmpty()
        ? \App\Models\MerchantBet::query()
            ->with([
                'merchant',
                'bet.translations',
            ])
            ->whereIn('merchant_id', $dashboardMerchantIds)
            ->whereNotNull('delivered_at')
            ->latest('delivered_at')
            ->limit(8)
            ->get()
        : collect();

    /*
    |--------------------------------------------------------------------------
    | PER-MERCHANT STATISTICS
    |--------------------------------------------------------------------------
    |
    | These cards are useful in All Merchants mode for choosing an account.
    |
    */

    $merchantStats = $merchants->map(function ($merchant) {
        $wallet = $merchant->wallets
            ->firstWhere('currency', 'USD');

        $balance = (float) ($wallet?->balance ?? 0);

        $delivered = $merchant->merchantBets()
            ->whereNotNull('delivered_at')
            ->count();

        $merchantMonthCost = abs((float) $merchant->balanceTransactions()
            ->whereIn('type', [
                'market_charge',
                'subscription_charge',
            ])
            ->whereBetween('created_at', [
                now()->startOfMonth(),
                now()->endOfMonth(),
            ])
            ->sum('amount'));

        $activeRules = $merchant->deliveryRules()
            ->where('is_active', true)
            ->count();

        $subscription = $merchant->subscriptions
            ->first();

        $billingPlan = $subscription?->billingPlan;

        $billingPeriod = $subscription
            ? $subscription->billingPeriods()
                ->where('status', 'open')
                ->latest('period_start')
                ->first()
            : null;

        return [
            'merchant' => $merchant,
            'balance' => $balance,
            'delivered' => $delivered,
            'month_cost' => $merchantMonthCost,
            'active_rules' => $activeRules,
            'subscription' => $subscription,
            'billing_plan' => $billingPlan,
            'billing_period' => $billingPeriod,
        ];
    });

    /*
    |--------------------------------------------------------------------------
    | SELECTED MERCHANT BILLING
    |--------------------------------------------------------------------------
    */

    $selectedStats = ! $isAllMerchants
        ? $merchantStats->first(
            fn ($stats) => (int) $stats['merchant']->id === (int) $selectedMerchant->id
        )
        : null;

    $selectedPlan = $selectedStats['billing_plan'] ?? null;
    $selectedPeriod = $selectedStats['billing_period'] ?? null;
@endphp

@section('title', 'Merchant Dashboard')

@section('content')

    <div class="space-y-6">

        {{-- HEADER --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">

            <div>
                <div class="flex flex-wrap items-center gap-3">
                    <h2 class="wr-text text-2xl font-black">
                        {{ $isAllMerchants
                            ? 'Merchant Dashboard'
                            : $selectedMerchant->name }}
                    </h2>

                    @if(! $isAllMerchants)
                        <span class="rounded-full bg-lime-400/15 px-2.5 py-1 text-[10px] font-black uppercase tracking-wider text-lime-500">
                        Current Merchant
                    </span>
                    @endif
                </div>

                <p class="wr-muted mt-2 text-sm">
                    @if($isAllMerchants)
                        Overview of all merchant accounts connected to your user.
                    @else
                        Dashboard for Merchant #{{ $selectedMerchant->id }}.
                    @endif
                </p>
            </div>

            <div
                class="rounded-xl border px-4 py-2.5"
                style="border-color: var(--wr-border); background: var(--wr-panel);"
            >
                <div class="wr-muted text-[10px] font-bold uppercase tracking-wider">
                    {{ $isAllMerchants ? 'Merchant Accounts' : 'Merchant ID' }}
                </div>

                <div class="wr-text mt-1 text-lg font-black">
                    {{ $isAllMerchants
                        ? number_format($merchants->count())
                        : '#' . $selectedMerchant->id }}
                </div>
            </div>

        </div>

        {{-- STATS --}}
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">

            <div class="wr-panel rounded-2xl border p-5">
                <div class="wr-muted text-xs font-bold uppercase tracking-wider">
                    {{ $isAllMerchants ? 'Total Balance' : 'Current Balance' }}
                </div>

                <div class="wr-text mt-3 text-2xl font-black">
                    ${{ number_format($totalBalance, 2) }}
                </div>

                <div class="wr-muted mt-2 text-xs">
                    @if($isAllMerchants)
                        Across {{ $merchants->count() }}
                        {{ \Illuminate\Support\Str::plural('merchant', $merchants->count()) }}
                    @else
                        {{ $selectedMerchant->name }}
                    @endif
                </div>
            </div>

            <div class="wr-panel rounded-2xl border p-5">
                <div class="wr-muted text-xs font-bold uppercase tracking-wider">
                    Markets Delivered
                </div>

                <div class="wr-text mt-3 text-2xl font-black">
                    {{ number_format($marketsDelivered) }}
                </div>

                <div class="wr-muted mt-2 text-xs">
                    {{ $isAllMerchants ? 'All merchant accounts' : $selectedMerchant->name }}
                </div>
            </div>

            <div class="wr-panel rounded-2xl border p-5">
                <div class="wr-muted text-xs font-bold uppercase tracking-wider">
                    This Month Cost
                </div>

                <div class="wr-text mt-3 text-2xl font-black">
                    ${{ number_format($monthCost, 2) }}
                </div>

                <div class="wr-muted mt-2 text-xs">
                    Market + subscription charges
                </div>
            </div>

            <div class="wr-panel rounded-2xl border p-5">
                <div class="wr-muted text-xs font-bold uppercase tracking-wider">
                    Active Auto Rules
                </div>

                <div class="wr-text mt-3 text-2xl font-black">
                    {{ number_format($activeAutoRules) }}
                </div>

                <div class="wr-muted mt-2 text-xs">
                    {{ $isAllMerchants ? 'Across all merchants' : $selectedMerchant->name }}
                </div>
            </div>

        </div>

        {{-- ALL MERCHANTS MODE --}}
        @if($isAllMerchants)

            <div class="wr-panel rounded-2xl border p-6">

                <div>
                    <h3 class="wr-text text-lg font-black">
                        Your Merchants
                    </h3>

                    <p class="wr-muted mt-1 text-sm">
                        Select a merchant to view its dashboard, markets, API access and billing.
                    </p>
                </div>

                @if($merchantStats->isEmpty())

                    <div
                        class="wr-muted mt-6 rounded-xl border border-dashed p-10 text-center text-sm"
                        style="border-color: var(--wr-border);"
                    >
                        No merchant accounts are assigned to your user.
                    </div>

                @else

                    <div class="mt-6 grid gap-4 xl:grid-cols-2">

                        @foreach($merchantStats as $stats)

                            @php
                                $merchant = $stats['merchant'];
                                $plan = $stats['billing_plan'];
                                $period = $stats['billing_period'];
                            @endphp

                            <div
                                class="rounded-2xl border p-5"
                                style="
                                border-color: var(--wr-border);
                                background: var(--wr-panel-secondary);
                            "
                            >

                                <div class="flex items-start justify-between gap-4">

                                    <div class="flex min-w-0 items-center gap-3">

                                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-lime-400 text-sm font-black text-[#06111f]">
                                            {{ strtoupper(
                                                mb_substr(
                                                    $merchant->name ?? 'M',
                                                    0,
                                                    1
                                                )
                                            ) }}
                                        </div>

                                        <div class="min-w-0">
                                            <h4 class="wr-text truncate font-black">
                                                {{ $merchant->name }}
                                            </h4>

                                            <div class="wr-muted mt-1 text-xs">
                                                Merchant #{{ $merchant->id }}
                                            </div>
                                        </div>

                                    </div>

                                    <form
                                        method="POST"
                                        action="{{ route('merchant.switch', $merchant) }}"
                                    >
                                        @csrf

                                        <button
                                            type="submit"
                                            class="rounded-xl bg-lime-400 px-4 py-2 text-xs font-black text-[#06111f] transition hover:bg-lime-300"
                                        >
                                            Open Merchant
                                        </button>
                                    </form>

                                </div>

                                <div class="mt-5 grid grid-cols-3 gap-3">

                                    <div>
                                        <div class="wr-muted text-[10px] font-bold uppercase tracking-wider">
                                            Balance
                                        </div>

                                        <div class="wr-text mt-1 text-sm font-black">
                                            ${{ number_format($stats['balance'], 2) }}
                                        </div>
                                    </div>

                                    <div>
                                        <div class="wr-muted text-[10px] font-bold uppercase tracking-wider">
                                            Delivered
                                        </div>

                                        <div class="wr-text mt-1 text-sm font-black">
                                            {{ number_format($stats['delivered']) }}
                                        </div>
                                    </div>

                                    <div>
                                        <div class="wr-muted text-[10px] font-bold uppercase tracking-wider">
                                            Month Cost
                                        </div>

                                        <div class="wr-text mt-1 text-sm font-black">
                                            ${{ number_format($stats['month_cost'], 2) }}
                                        </div>
                                    </div>

                                </div>

                                <div
                                    class="mt-5 grid gap-3 border-t pt-4 sm:grid-cols-2"
                                    style="border-color: var(--wr-border);"
                                >

                                    <div>
                                        <div class="wr-muted text-[10px] font-bold uppercase tracking-wider">
                                            Billing Plan
                                        </div>

                                        <div class="wr-text mt-1 text-sm font-bold">
                                            {{ $plan?->name ?? 'Not assigned' }}
                                        </div>

                                        @if($plan)
                                            <div class="wr-muted mt-1 text-xs">
                                                {{ $plan->billing_type?->label() ?? '—' }}
                                            </div>
                                        @endif
                                    </div>

                                    <div>
                                        <div class="wr-muted text-[10px] font-bold uppercase tracking-wider">
                                            Usage
                                        </div>

                                        <div class="wr-text mt-1 text-sm font-bold">

                                            @if($plan?->is_unlimited)

                                                Unlimited

                                            @elseif($period && $period->included_markets !== null)

                                                {{ number_format($period->markets_used) }}
                                                /
                                                {{ number_format($period->included_markets) }}

                                            @elseif($period)

                                                {{ number_format($period->markets_used) }} markets

                                            @else

                                                &mdash;

                                            @endif

                                        </div>
                                    </div>

                                </div>

                            </div>

                        @endforeach

                    </div>

                @endif

            </div>

            {{-- SELECTED MERCHANT MODE --}}
        @else

            <div class="grid gap-6 xl:grid-cols-2">

                {{-- MERCHANT INFO --}}
                <div class="wr-panel rounded-2xl border p-6">

                    <h3 class="wr-text text-lg font-black">
                        Merchant
                    </h3>

                    <p class="wr-muted mt-1 text-sm">
                        Current merchant account.
                    </p>

                    <div class="mt-6 flex items-center gap-4">

                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-lime-400 text-base font-black text-[#06111f]">
                            {{ strtoupper(
                                mb_substr(
                                    $selectedMerchant->name ?? 'M',
                                    0,
                                    1
                                )
                            ) }}
                        </div>

                        <div>
                            <div class="wr-text text-base font-black">
                                {{ $selectedMerchant->name }}
                            </div>

                            <div class="wr-muted mt-1 text-xs">
                                Merchant #{{ $selectedMerchant->id }}
                            </div>
                        </div>

                    </div>

                    <div
                        class="mt-6 grid gap-4 border-t pt-5 sm:grid-cols-2"
                        style="border-color: var(--wr-border);"
                    >
                        <div>
                            <div class="wr-muted text-[10px] font-bold uppercase tracking-wider">
                                Market URL
                            </div>

                            <div class="wr-text mt-1 break-all text-xs font-bold">
                                {{ $selectedMerchant->market_url ?: 'Not configured' }}
                            </div>
                        </div>

                        <div>
                            <div class="wr-muted text-[10px] font-bold uppercase tracking-wider">
                                Callback URL
                            </div>

                            <div class="wr-text mt-1 break-all text-xs font-bold">
                                {{ $selectedMerchant->callback_url ?: 'Not configured' }}
                            </div>
                        </div>
                    </div>

                </div>

                {{-- BILLING --}}
                <div class="wr-panel rounded-2xl border p-6">

                    <h3 class="wr-text text-lg font-black">
                        Billing
                    </h3>

                    <p class="wr-muted mt-1 text-sm">
                        Current billing configuration for {{ $selectedMerchant->name }}.
                    </p>

                    <div class="mt-6 space-y-4">

                        <div class="flex items-center justify-between gap-4">
                        <span class="wr-muted text-sm">
                            Plan
                        </span>

                            <span class="wr-text text-right text-sm font-bold">
                            {{ $selectedPlan?->name ?? 'Not assigned' }}
                        </span>
                        </div>

                        <div class="flex items-center justify-between gap-4">
                        <span class="wr-muted text-sm">
                            Billing type
                        </span>

                            <span class="wr-text text-right text-sm font-bold">
                            {{ $selectedPlan?->billing_type?->label() ?? '—' }}
                        </span>
                        </div>

                        <div class="flex items-center justify-between gap-4">
                        <span class="wr-muted text-sm">
                            Usage
                        </span>

                            <span class="wr-text text-right text-sm font-bold">

                            @if($selectedPlan?->is_unlimited)

                                    Unlimited

                                @elseif(
                                    $selectedPeriod
                                    &&
                                    $selectedPeriod->included_markets !== null
                                )

                                    {{ number_format($selectedPeriod->markets_used) }}
                                    /
                                    {{ number_format($selectedPeriod->included_markets) }}

                                @elseif($selectedPeriod)

                                    {{ number_format($selectedPeriod->markets_used) }}
                                    markets

                                @else

                                    &mdash;

                                @endif

                        </span>
                        </div>

                        <div
                            class="flex items-center justify-between gap-4 border-t pt-4"
                            style="border-color: var(--wr-border);"
                        >
                        <span class="wr-muted text-sm">
                            Available balance
                        </span>

                            <span class="wr-text text-right text-sm font-black">
                            ${{ number_format($totalBalance, 2) }}
                        </span>
                        </div>

                    </div>

                </div>

            </div>

        @endif

        {{-- RECENT DELIVERIES --}}
        <div class="wr-panel rounded-2xl border p-6">

            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

                <div>
                    <h3 class="wr-text text-lg font-black">
                        Recent Deliveries
                    </h3>

                    <p class="wr-muted mt-1 text-sm">
                        @if($isAllMerchants)
                            Latest markets delivered across all your merchant accounts.
                        @else
                            Latest markets delivered to {{ $selectedMerchant->name }}.
                        @endif
                    </p>
                </div>

                @if(! $isAllMerchants)
                    <a
                        href="{{ route('merchant.markets') }}"
                        class="inline-flex shrink-0 items-center justify-center rounded-xl bg-lime-400 px-4 py-2.5 text-xs font-black text-[#06111f] transition hover:bg-lime-300"
                    >
                        View All Markets &rarr;
                    </a>
                @endif

            </div>

            @if($recentDeliveries->isEmpty())

                <div
                    class="wr-muted mt-6 rounded-xl border border-dashed p-8 text-center text-sm"
                    style="border-color: var(--wr-border);"
                >
                    @if($isAllMerchants)
                        No deliveries yet.
                    @else
                        No markets have been delivered to {{ $selectedMerchant->name }} yet.
                    @endif
                </div>

            @else

                <div class="mt-6 overflow-x-auto">

                    <table class="w-full min-w-[720px]">

                        <thead>
                        <tr
                            class="wr-muted border-b text-left text-[10px] font-bold uppercase tracking-wider"
                            style="border-color: var(--wr-border);"
                        >
                            <th class="px-3 py-3">
                                Market
                            </th>

                            @if($isAllMerchants)
                                <th class="px-3 py-3">
                                    Merchant
                                </th>
                            @endif

                            <th class="px-3 py-3">
                                Status
                            </th>

                            <th class="px-3 py-3">
                                Delivered
                            </th>
                        </tr>
                        </thead>

                        <tbody>

                        @foreach($recentDeliveries as $delivery)

                            @php
                                $translation =
                                    $delivery->bet
                                        ?->translations
                                        ?->first();

                                $marketTitle =
                                    $translation?->title
                                    ?? $translation?->question
                                    ?? 'Market #' . $delivery->bet_id;
                            @endphp

                            <tr
                                class="border-b last:border-b-0"
                                style="border-color: var(--wr-border);"
                            >

                                <td class="px-3 py-4">
                                    <div class="wr-text max-w-[360px] truncate text-sm font-bold">
                                        {{ $marketTitle }}
                                    </div>

                                    <div class="wr-muted mt-1 text-xs">
                                        Market #{{ $delivery->bet_id }}
                                    </div>
                                </td>

                                @if($isAllMerchants)
                                    <td class="px-3 py-4">
                                        <div class="wr-text text-sm font-bold">
                                            {{ $delivery->merchant?->name ?? '—' }}
                                        </div>
                                    </td>
                                @endif

                                <td class="px-3 py-4">
                                    <span class="rounded-full bg-lime-400/10 px-2.5 py-1 text-[10px] font-black uppercase tracking-wider text-lime-500">
                                        {{ $delivery->status?->label()
                                            ?? $delivery->status?->value
                                            ?? $delivery->status
                                            ?? 'Delivered' }}
                                    </span>
                                </td>

                                <td class="wr-muted px-3 py-4 text-sm">
                                    {{ $delivery->delivered_at
                                        ?->format('Y-m-d H:i') ?? '—' }}
                                </td>

                            </tr>

                        @endforeach

                        </tbody>

                    </table>

                </div>

            @endif

        </div>

    </div>

@endsection
