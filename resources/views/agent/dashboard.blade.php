@extends('dashboards.layouts.dashboard')

@php
    /*
    |--------------------------------------------------------------------------
    | AGENT DASHBOARD
    |--------------------------------------------------------------------------
    */

    $user = auth()->user();

    /*
    |--------------------------------------------------------------------------
    | SELECTED MONTH
    |--------------------------------------------------------------------------
    |
    | Expected format: YYYY-MM
    | Invalid values fall back to the current month.
    |
    */

    $requestedMonth = request()->query('month', now()->format('Y-m'));

    try {
        $selectedMonth = \Carbon\Carbon::createFromFormat(
            'Y-m',
            $requestedMonth
        )->startOfMonth();

        if ($selectedMonth->format('Y-m') !== $requestedMonth) {
            throw new \InvalidArgumentException();
        }
    } catch (\Throwable $e) {
        $selectedMonth = now()->startOfMonth();
    }

    $monthStart = $selectedMonth->copy()->startOfMonth();
    $monthEnd = $selectedMonth->copy()->endOfMonth();

    /*
    |--------------------------------------------------------------------------
    | AGENT MERCHANTS
    |--------------------------------------------------------------------------
    |
    | Only merchants explicitly assigned to the authenticated agent.
    |
    */

    $merchants = $user->agentMerchants()
        ->orderBy('name')
        ->orderBy('id')
        ->get();

    $merchantIds = $merchants->pluck('id');

    /*
    |--------------------------------------------------------------------------
    | MONTHLY DELIVERY STATISTICS
    |--------------------------------------------------------------------------
    |
    | merchant_bets represents markets delivered to merchants.
    | Only successfully delivered rows are counted.
    |
    */

    $deliveryCounts = $merchantIds->isNotEmpty()
        ? \App\Models\MerchantBet::query()
            ->whereIn('merchant_id', $merchantIds)
            ->whereNotNull('delivered_at')
            ->whereBetween('delivered_at', [
                $monthStart,
                $monthEnd,
            ])
            ->selectRaw('merchant_id, COUNT(*) as delivered_count')
            ->groupBy('merchant_id')
            ->pluck('delivered_count', 'merchant_id')
        : collect();

    /*
    |--------------------------------------------------------------------------
    | PER-MERCHANT STATISTICS
    |--------------------------------------------------------------------------
    */

    $merchantStats = $merchants->map(function ($merchant) use ($deliveryCounts) {
        return [
            'merchant' => $merchant,
            'delivered' => (int) ($deliveryCounts[$merchant->id] ?? 0),
        ];
    });

    $totalDelivered = $merchantStats->sum('delivered');
    $activeMerchants = $merchantStats
        ->where('delivered', '>', 0)
        ->count();

    /*
    |--------------------------------------------------------------------------
    | MONTH NAVIGATION
    |--------------------------------------------------------------------------
    */

    $previousMonth = $selectedMonth
        ->copy()
        ->subMonth()
        ->format('Y-m');

    $nextMonthDate = $selectedMonth
        ->copy()
        ->addMonth();

    $nextMonth = $nextMonthDate->format('Y-m');

    $canGoNext = $nextMonthDate->startOfMonth()
        ->lte(now()->startOfMonth());
@endphp

@section('title', 'Agent Dashboard')

@section('content')

    <div class="space-y-6">

        {{-- HEADER --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">

            <div>
                <h1 class="text-2xl font-semibold wr-text">
                    Agent Dashboard
                </h1>

                <p class="mt-1 text-sm wr-muted">
                    Delivery statistics for merchants assigned to your account.
                </p>
            </div>

            {{-- MONTH SELECTOR --}}
            <form method="GET">
                <input
                    type="month"
                    name="month"
                    value="{{ $selectedMonth->format('Y-m') }}"
                    max="{{ now()->format('Y-m') }}"
                    onchange="this.form.submit()"
                    class="rounded-xl border px-4 py-2.5 text-sm font-semibold outline-none transition focus:border-lime-400"
                    style="
                        border-color: var(--wr-border);
                        background: var(--wr-input);
                        color: var(--wr-text);
                    "
                >
            </form>

        </div>


        {{-- SUMMARY --}}
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">

            <div class="wr-panel rounded-xl border p-5">
                <div class="text-sm wr-muted">
                    Assigned merchants
                </div>

                <div class="mt-2 text-2xl font-semibold wr-text">
                    {{ number_format($merchants->count()) }}
                </div>
            </div>

            <div class="wr-panel rounded-xl border p-5">
                <div class="text-sm wr-muted">
                    Active merchants
                </div>

                <div class="mt-2 text-2xl font-semibold wr-text">
                    {{ number_format($activeMerchants) }}
                </div>

                <div class="mt-1 text-xs wr-muted">
                    With deliveries this month
                </div>
            </div>

            <div class="wr-panel rounded-xl border p-5">
                <div class="text-sm wr-muted">
                    Markets delivered
                </div>

                <div class="mt-2 text-2xl font-semibold wr-text">
                    {{ number_format($totalDelivered) }}
                </div>

                <div class="mt-1 text-xs wr-muted">
                    {{ $selectedMonth->format('F Y') }}
                </div>
            </div>

        </div>


        {{-- MONTH NAVIGATION --}}
        <div class="flex items-center justify-between">

            <a
                href="{{ route('agent.dashboard', ['month' => $previousMonth]) }}"
                class="rounded-xl border px-4 py-2 text-sm font-semibold transition wr-nav"
                style="border-color: var(--wr-border);"
            >
                ← Previous month
            </a>

            <div class="text-sm font-semibold wr-text">
                {{ $selectedMonth->format('F Y') }}
            </div>

            @if($canGoNext)
                <a
                    href="{{ route('agent.dashboard', ['month' => $nextMonth]) }}"
                    class="rounded-xl border px-4 py-2 text-sm font-semibold transition wr-nav"
                    style="border-color: var(--wr-border);"
                >
                    Next month →
                </a>
            @else
                <span
                    class="cursor-not-allowed rounded-xl border px-4 py-2 text-sm font-semibold opacity-40"
                    style="border-color: var(--wr-border); color: var(--wr-muted);"
                >
                    Next month →
                </span>
            @endif

        </div>


        {{-- MERCHANT STATISTICS --}}
        <div class="wr-panel overflow-hidden rounded-xl border">

            <div
                class="border-b px-5 py-4"
                style="border-color: var(--wr-border);"
            >
                <h2 class="font-semibold wr-text">
                    Merchant deliveries
                </h2>

                <p class="mt-1 text-xs wr-muted">
                    {{ $selectedMonth->format('F Y') }}
                </p>
            </div>

            @if($merchants->isEmpty())

                <div class="px-5 py-10 text-center">
                    <div class="font-semibold wr-text">
                        No merchants assigned
                    </div>

                    <div class="mt-1 text-sm wr-muted">
                        There are currently no merchants assigned to your agent account.
                    </div>
                </div>

            @else

                <div class="overflow-x-auto">

                    <table class="w-full text-left">

                        <thead
                            style="background: var(--wr-panel-secondary);"
                        >
                        <tr>
                            <th class="px-5 py-3 text-xs font-bold uppercase tracking-wider wr-muted">
                                Merchant
                            </th>

                            <th class="px-5 py-3 text-right text-xs font-bold uppercase tracking-wider wr-muted">
                                Markets delivered
                            </th>
                        </tr>
                        </thead>

                        <tbody>

                        @foreach($merchantStats as $stats)

                            <tr
                                class="border-t"
                                style="border-color: var(--wr-border-soft);"
                            >
                                <td class="px-5 py-4">

                                    <div class="font-semibold wr-text">
                                        {{ $stats['merchant']->name }}
                                    </div>

                                    <div class="mt-1 text-xs wr-muted">
                                        Merchant #{{ $stats['merchant']->id }}
                                    </div>

                                </td>

                                <td class="px-5 py-4 text-right">

                                    <span class="text-lg font-semibold wr-text">
                                        {{ number_format($stats['delivered']) }}
                                    </span>

                                </td>
                            </tr>

                        @endforeach

                        </tbody>

                        <tfoot
                            style="background: var(--wr-panel-secondary);"
                        >
                        <tr
                            class="border-t"
                            style="border-color: var(--wr-border);"
                        >
                            <td class="px-5 py-4 font-bold wr-text">
                                Total
                            </td>

                            <td class="px-5 py-4 text-right text-lg font-bold wr-text">
                                {{ number_format($totalDelivered) }}
                            </td>
                        </tr>
                        </tfoot>

                    </table>

                </div>

            @endif

        </div>

    </div>

@endsection
