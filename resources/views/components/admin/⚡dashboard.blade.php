<?php

use App\Enums\BetStatus;
use App\Enums\UserRole;
use App\Models\Bet;
use App\Models\Merchant;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Component;

new class extends Component
{
    public function with(): array
    {
        $monthStart = now()->startOfMonth();

        /*
        |--------------------------------------------------------------------------
        | Users
        |--------------------------------------------------------------------------
        */

        $usersTotal = User::query()->count();

        $usersThisMonth = User::query()
            ->where('created_at', '>=', $monthStart)
            ->count();


        /*
        |--------------------------------------------------------------------------
        | Users by role
        |--------------------------------------------------------------------------
        */

        $usersByRoleTotals = User::query()
            ->selectRaw('role, COUNT(*) as aggregate')
            ->groupBy('role')
            ->pluck('aggregate', 'role');

        $usersByRoleMonth = User::query()
            ->where('created_at', '>=', $monthStart)
            ->selectRaw('role, COUNT(*) as aggregate')
            ->groupBy('role')
            ->pluck('aggregate', 'role');

        $usersByRole = collect(UserRole::cases())
            ->map(function (UserRole $role) use (
                $usersByRoleTotals,
                $usersByRoleMonth
            ) {
                return [
                    'value' => $role->value,
                    'label' => $role->label(),

                    'total' => (int) (
                        $usersByRoleTotals[$role->value] ?? 0
                    ),

                    'month' => (int) (
                        $usersByRoleMonth[$role->value] ?? 0
                    ),
                ];
            });


        /*
        |--------------------------------------------------------------------------
        | Merchants
        |--------------------------------------------------------------------------
        */

        $merchantsTotal = Merchant::query()->count();

        $merchantsThisMonth = Merchant::query()
            ->where('created_at', '>=', $monthStart)
            ->count();


        /*
        |--------------------------------------------------------------------------
        | Markets
        |--------------------------------------------------------------------------
        */

        $marketsTotal = Bet::query()->count();

        $marketsThisMonth = Bet::query()
            ->where('created_at', '>=', $monthStart)
            ->count();


        /*
        |--------------------------------------------------------------------------
        | Markets by status
        |--------------------------------------------------------------------------
        */

        $marketsByStatusTotals = Bet::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $marketsByStatusMonth = Bet::query()
            ->where('created_at', '>=', $monthStart)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $marketsByStatus = collect(BetStatus::cases())
            ->map(function (BetStatus $status) use (
                $marketsByStatusTotals,
                $marketsByStatusMonth
            ) {
                return [
                    'value' => $status->value,
                    'label' => $status->label(),

                    'total' => (int) (
                        $marketsByStatusTotals[$status->value] ?? 0
                    ),

                    'month' => (int) (
                        $marketsByStatusMonth[$status->value] ?? 0
                    ),
                ];
            });


        return [
            'monthLabel' => Carbon::now()->format('F Y'),

            'usersTotal' => $usersTotal,
            'usersThisMonth' => $usersThisMonth,

            'merchantsTotal' => $merchantsTotal,
            'merchantsThisMonth' => $merchantsThisMonth,

            'marketsTotal' => $marketsTotal,
            'marketsThisMonth' => $marketsThisMonth,

            'usersByRole' => $usersByRole,

            'marketsByStatus' => $marketsByStatus,
        ];
    }
};

?>

<div class="space-y-6">

    {{-- ============================================================= --}}
    {{-- HEADER                                                        --}}
    {{-- ============================================================= --}}

    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">

        <div>
            <div class="text-xs font-semibold uppercase tracking-[0.18em] wr-muted">
                Administration
            </div>

            <h1 class="mt-1 text-2xl font-bold wr-text">
                Platform Overview
            </h1>

            <p class="mt-1 text-sm wr-muted">
                Users, merchants and prediction markets across the platform.
            </p>
        </div>

        <div
            class="inline-flex items-center gap-2 rounded-xl border px-3 py-2 text-sm wr-muted"
            style="border-color: var(--wr-border);"
        >
            <svg
                class="h-4 w-4"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.8"
            >
                <rect x="3" y="5" width="18" height="16" rx="2"/>
                <path d="M16 3v4M8 3v4M3 10h18"/>
            </svg>

            {{ $monthLabel }}
        </div>

    </div>


    {{-- ============================================================= --}}
    {{-- MAIN KPIs                                                     --}}
    {{-- ============================================================= --}}

    <div class="grid gap-4 md:grid-cols-3">

        {{-- USERS --}}

        <a
            href="{{ route('admin.users.index') }}"
            class="wr-panel group block rounded-2xl border p-5 transition hover:-translate-y-0.5 hover:shadow-lg"
            style="border-color: var(--wr-border);"
        >
            <div class="flex items-start justify-between gap-4">

                <div>
                    <div class="text-sm font-medium wr-muted">
                        Users
                    </div>

                    <div class="mt-3 text-3xl font-bold wr-text">
                        {{ number_format($usersTotal) }}
                    </div>
                </div>

                <div
                    class="flex h-11 w-11 items-center justify-center rounded-xl border"
                    style="border-color: var(--wr-border);"
                >
                    <svg
                        class="h-5 w-5 wr-text"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                    >
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                </div>

            </div>

            <div class="mt-5 flex items-center justify-between">

                <span class="text-xs wr-muted">
                    New this month
                </span>

                <span class="text-sm font-semibold text-lime-500">
                    +{{ number_format($usersThisMonth) }}
                </span>

            </div>
        </a>


        {{-- MERCHANTS --}}

        <div
            class="wr-panel rounded-2xl border p-5"
            style="border-color: var(--wr-border);"
        >
            <div class="flex items-start justify-between gap-4">

                <div>
                    <div class="text-sm font-medium wr-muted">
                        Merchants
                    </div>

                    <div class="mt-3 text-3xl font-bold wr-text">
                        {{ number_format($merchantsTotal) }}
                    </div>
                </div>

                <div
                    class="flex h-11 w-11 items-center justify-center rounded-xl border"
                    style="border-color: var(--wr-border);"
                >
                    <svg
                        class="h-5 w-5 wr-text"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                    >
                        <path d="M3 9l2-5h14l2 5"/>
                        <path d="M5 13v7h14v-7"/>
                        <path d="M9 20v-6h6v6"/>
                        <path d="M3 9a3 3 0 0 0 6 0 3 3 0 0 0 6 0 3 3 0 0 0 6 0"/>
                    </svg>
                </div>

            </div>

            <div class="mt-5 flex items-center justify-between">

                <span class="text-xs wr-muted">
                    New this month
                </span>

                <span class="text-sm font-semibold text-lime-500">
                    +{{ number_format($merchantsThisMonth) }}
                </span>

            </div>
        </div>


        {{-- MARKETS --}}

        <div
            class="wr-panel rounded-2xl border p-5"
            style="border-color: var(--wr-border);"
        >
            <div class="flex items-start justify-between gap-4">

                <div>
                    <div class="text-sm font-medium wr-muted">
                        Markets
                    </div>

                    <div class="mt-3 text-3xl font-bold wr-text">
                        {{ number_format($marketsTotal) }}
                    </div>
                </div>

                <div
                    class="flex h-11 w-11 items-center justify-center rounded-xl border"
                    style="border-color: var(--wr-border);"
                >
                    <svg
                        class="h-5 w-5 wr-text"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                    >
                        <path d="M4 19V9"/>
                        <path d="M10 19V5"/>
                        <path d="M16 19v-7"/>
                        <path d="M22 19V3"/>
                    </svg>
                </div>

            </div>

            <div class="mt-5 flex items-center justify-between">

                <span class="text-xs wr-muted">
                    New this month
                </span>

                <span class="text-sm font-semibold text-lime-500">
                    +{{ number_format($marketsThisMonth) }}
                </span>

            </div>
        </div>

    </div>


    {{-- ============================================================= --}}
    {{-- DETAILS                                                       --}}
    {{-- ============================================================= --}}

    <div class="grid gap-6 xl:grid-cols-2">

        {{-- USERS BY ROLE --}}

        <section
            class="wr-panel overflow-hidden rounded-2xl border"
            style="border-color: var(--wr-border);"
        >

            <div
                class="border-b px-5 py-4"
                style="border-color: var(--wr-border);"
            >
                <h2 class="font-semibold wr-text">
                    Users by role
                </h2>

                <p class="mt-1 text-xs wr-muted">
                    Current account distribution.
                </p>
            </div>


            <div class="divide-y" style="border-color: var(--wr-border);">

                @foreach($usersByRole as $item)

                    <div
                        class="flex items-center justify-between gap-4 px-5 py-4"
                        style="border-color: var(--wr-border);"
                    >

                        <div class="min-w-0">

                            <div class="font-medium wr-text">
                                {{ $item['label'] }}
                            </div>

                            <div class="mt-1 text-xs wr-muted">
                                {{ $item['month'] > 0 ? '+' : '' }}{{ number_format($item['month']) }}
                                this month
                            </div>

                        </div>


                        <div class="text-right">

                            <div class="text-xl font-bold wr-text">
                                {{ number_format($item['total']) }}
                            </div>

                            <div class="text-[11px] uppercase tracking-wide wr-muted">
                                total
                            </div>

                        </div>

                    </div>

                @endforeach

            </div>

        </section>


        {{-- MARKETS BY STATUS --}}

        <section
            class="wr-panel overflow-hidden rounded-2xl border"
            style="border-color: var(--wr-border);"
        >

            <div
                class="border-b px-5 py-4"
                style="border-color: var(--wr-border);"
            >
                <h2 class="font-semibold wr-text">
                    Markets by status
                </h2>

                <p class="mt-1 text-xs wr-muted">
                    Current market lifecycle distribution.
                </p>
            </div>


            <div class="divide-y" style="border-color: var(--wr-border);">

                @foreach($marketsByStatus as $item)

                    <div
                        class="flex items-center justify-between gap-4 px-5 py-4"
                        style="border-color: var(--wr-border);"
                    >

                        <div class="min-w-0">

                            <div class="font-medium wr-text">
                                {{ $item['label'] }}
                            </div>

                            <div class="mt-1 text-xs wr-muted">
                                {{ $item['month'] > 0 ? '+' : '' }}{{ number_format($item['month']) }}
                                this month
                            </div>

                        </div>


                        <div class="text-right">

                            <div class="text-xl font-bold wr-text">
                                {{ number_format($item['total']) }}
                            </div>

                            <div class="text-[11px] uppercase tracking-wide wr-muted">
                                total
                            </div>

                        </div>

                    </div>

                @endforeach

            </div>

        </section>

    </div>

</div>
