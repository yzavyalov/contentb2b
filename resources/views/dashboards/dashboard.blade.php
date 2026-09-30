@extends('dashboards.layouts.dashboard')

@section('title', 'Admin Dashboard')

@section('content')

    @php
        /*
        |--------------------------------------------------------------------------
        | Selected month
        |--------------------------------------------------------------------------
        */

        $requestedMonth = request()->query('month');

        try {
            $selectedMonth = $requestedMonth
                ? \Carbon\Carbon::createFromFormat('!Y-m', $requestedMonth)
                : now()->startOfMonth();
        } catch (\Throwable $e) {
            $selectedMonth = now()->startOfMonth();
        }

        $selectedMonth = $selectedMonth->startOfMonth();

        /*
         * Do not allow future months.
         */
        if ($selectedMonth->gt(now()->startOfMonth())) {
            $selectedMonth = now()->startOfMonth();
        }

        $monthStart = $selectedMonth->copy()->startOfMonth();
        $monthEnd = $selectedMonth->copy()->addMonth()->startOfMonth();

        $monthValue = $selectedMonth->format('Y-m');
        $monthLabel = $selectedMonth->format('F Y');
        $monthShortLabel = $selectedMonth->format('M Y');

        $previousMonth = $selectedMonth->copy()->subMonth();

        $nextMonth = $selectedMonth->copy()->addMonth();

        $canGoNext = $nextMonth->lte(now()->startOfMonth());


        /*
        |--------------------------------------------------------------------------
        | Main counters
        |--------------------------------------------------------------------------
        */

        $usersTotal = \App\Models\User::query()->count();

        $usersInMonth = \App\Models\User::query()
            ->where('created_at', '>=', $monthStart)
            ->where('created_at', '<', $monthEnd)
            ->count();


        $merchantsTotal = \App\Models\Merchant::query()->count();

        $merchantsInMonth = \App\Models\Merchant::query()
            ->where('created_at', '>=', $monthStart)
            ->where('created_at', '<', $monthEnd)
            ->count();


        $marketsTotal = \App\Models\Bet::query()->count();

        $marketsInMonth = \App\Models\Bet::query()
            ->where('created_at', '>=', $monthStart)
            ->where('created_at', '<', $monthEnd)
            ->count();


        /*
        |--------------------------------------------------------------------------
        | Users by role
        |--------------------------------------------------------------------------
        */

        $userRoleTotals = \App\Models\User::query()
            ->selectRaw('role, COUNT(*) as aggregate')
            ->groupBy('role')
            ->pluck('aggregate', 'role');

        $userRoleMonth = \App\Models\User::query()
            ->where('created_at', '>=', $monthStart)
            ->where('created_at', '<', $monthEnd)
            ->selectRaw('role, COUNT(*) as aggregate')
            ->groupBy('role')
            ->pluck('aggregate', 'role');


        /*
        |--------------------------------------------------------------------------
        | Markets by current status
        |--------------------------------------------------------------------------
        |
        | Month value here means:
        | market was created during selected month
        | and currently has this status.
        |
        */

        $marketStatusTotals = \App\Models\Bet::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $marketStatusMonth = \App\Models\Bet::query()
            ->where('created_at', '>=', $monthStart)
            ->where('created_at', '<', $monthEnd)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');


        /*
        |--------------------------------------------------------------------------
        | Activity during selected month
        |--------------------------------------------------------------------------
        */

        $marketsApprovedInMonth = \App\Models\Bet::query()
            ->whereNotNull('approved_at')
            ->where('approved_at', '>=', $monthStart)
            ->where('approved_at', '<', $monthEnd)
            ->count();

        $marketsPublishedInMonth = \App\Models\Bet::query()
            ->whereNotNull('published_at')
            ->where('published_at', '>=', $monthStart)
            ->where('published_at', '<', $monthEnd)
            ->count();

        $marketsResolvedInMonth = \App\Models\Bet::query()
            ->whereNotNull('resolved_at')
            ->where('resolved_at', '>=', $monthStart)
            ->where('resolved_at', '<', $monthEnd)
            ->count();

        $marketsRejectedInMonth = \App\Models\Bet::query()
            ->whereNotNull('rejected_at')
            ->where('rejected_at', '>=', $monthStart)
            ->where('rejected_at', '<', $monthEnd)
            ->count();


        /*
        |--------------------------------------------------------------------------
        | Date labels
        |--------------------------------------------------------------------------
        */

        $isCurrentMonth = $selectedMonth->isSameMonth(now());

        $activityEndLabel = $isCurrentMonth
            ? now()->format('M j')
            : $selectedMonth->copy()->endOfMonth()->format('M j');
    @endphp


    <div class="space-y-6">

        {{-- ========================================================= --}}
        {{-- HEADER --}}
        {{-- ========================================================= --}}

        <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">

            <div>
                <div class="wr-muted-soft text-xs font-bold uppercase tracking-[0.18em]">
                    Administration
                </div>

                <h2 class="wr-text mt-1 text-2xl font-black">
                    Platform Overview
                </h2>

                <p class="wr-muted mt-1 text-sm">
                    Users, merchants and prediction markets across wrangle.win.
                </p>
            </div>


            {{-- MONTH SELECTOR --}}

            <div class="flex flex-wrap items-center gap-2">

                {{-- PREVIOUS --}}

                <a
                    href="{{ route('dashboard', ['month' => $previousMonth->format('Y-m')]) }}"
                    class="wr-panel inline-flex h-11 items-center justify-center gap-2 rounded-xl border px-4 text-sm font-bold transition hover:border-lime-400"
                    title="Previous month"
                >
                    <svg
                        class="h-4 w-4"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path d="m15 18-6-6 6-6"/>
                    </svg>

                    <span class="hidden sm:inline">
                        Previous
                    </span>
                </a>


                {{-- MONTH INPUT --}}

                <form
                    method="GET"
                    action="{{ route('dashboard') }}"
                    class="wr-panel flex h-11 items-center gap-3 rounded-xl border px-3"
                >
                    <svg
                        class="h-4 w-4 shrink-0 text-lime-500"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <rect x="3" y="5" width="18" height="16" rx="2"/>
                        <path d="M16 3v4M8 3v4M3 10h18"/>
                    </svg>

                    <input
                        type="month"
                        name="month"
                        value="{{ $monthValue }}"
                        max="{{ now()->format('Y-m') }}"
                        onchange="this.form.submit()"
                        class="wr-text cursor-pointer border-0 bg-transparent text-sm font-bold outline-none"
                        style="color-scheme: inherit;"
                        aria-label="Dashboard month"
                    >
                </form>


                {{-- NEXT --}}

                @if($canGoNext)

                    <a
                        href="{{ route('dashboard', ['month' => $nextMonth->format('Y-m')]) }}"
                        class="wr-panel inline-flex h-11 items-center justify-center gap-2 rounded-xl border px-4 text-sm font-bold transition hover:border-lime-400"
                        title="Next month"
                    >
                        <span class="hidden sm:inline">
                            Next
                        </span>

                        <svg
                            class="h-4 w-4"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path d="m9 18 6-6-6-6"/>
                        </svg>
                    </a>

                @else

                    <div
                        class="inline-flex h-11 cursor-not-allowed items-center justify-center gap-2 rounded-xl border px-4 text-sm font-bold opacity-40"
                        style="
                            border-color: var(--wr-border);
                            color: var(--wr-muted);
                        "
                        title="Current month"
                    >
                        <span class="hidden sm:inline">
                            Next
                        </span>

                        <svg
                            class="h-4 w-4"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path d="m9 18 6-6-6-6"/>
                        </svg>
                    </div>

                @endif

            </div>

        </div>


        {{-- ========================================================= --}}
        {{-- SELECTED PERIOD --}}
        {{-- ========================================================= --}}

        <div
            class="flex flex-col gap-2 rounded-2xl border px-5 py-4 sm:flex-row sm:items-center sm:justify-between"
            style="
                border-color: rgba(163, 230, 53, .22);
                background: rgba(163, 230, 53, .06);
            "
        >
            <div>
                <div class="text-xs font-bold uppercase tracking-[0.15em] text-lime-500">
                    Selected period
                </div>

                <div class="wr-text mt-1 text-lg font-black">
                    {{ $monthLabel }}
                </div>
            </div>

            @if($isCurrentMonth)

                <div class="text-xs font-bold text-lime-500">
                    Current month
                </div>

            @else

                <a
                    href="{{ route('dashboard') }}"
                    class="text-xs font-bold text-lime-500 transition hover:text-lime-400"
                >
                    Return to current month
                </a>

            @endif
        </div>


        {{-- ========================================================= --}}
        {{-- MAIN COUNTERS --}}
        {{-- ========================================================= --}}

        <div class="grid gap-4 md:grid-cols-3">

            {{-- USERS --}}

            <a
                href="{{ route('admin.users.index') }}"
                class="wr-panel group rounded-2xl border p-6 transition hover:-translate-y-0.5"
            >
                <div class="flex items-start justify-between gap-4">

                    <div>
                        <div class="wr-muted text-sm font-semibold">
                            Users
                        </div>

                        <div class="wr-text mt-3 text-4xl font-black">
                            {{ number_format($usersTotal) }}
                        </div>
                    </div>

                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-xl"
                        style="
                            background: var(--wr-panel-secondary);
                            color: #a3e635;
                        "
                    >
                        <svg
                            class="h-6 w-6"
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

                <div
                    class="mt-5 flex items-center justify-between border-t pt-4"
                    style="border-color: var(--wr-border);"
                >
                    <span class="wr-muted text-xs">
                        New in {{ $monthShortLabel }}
                    </span>

                    <span class="font-black text-lime-500">
                        +{{ number_format($usersInMonth) }}
                    </span>
                </div>
            </a>


            {{-- MERCHANTS --}}

            <div class="wr-panel rounded-2xl border p-6">

                <div class="flex items-start justify-between gap-4">

                    <div>
                        <div class="wr-muted text-sm font-semibold">
                            Merchants
                        </div>

                        <div class="wr-text mt-3 text-4xl font-black">
                            {{ number_format($merchantsTotal) }}
                        </div>
                    </div>

                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-xl"
                        style="
                            background: var(--wr-panel-secondary);
                            color: #a3e635;
                        "
                    >
                        <svg
                            class="h-6 w-6"
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

                <div
                    class="mt-5 flex items-center justify-between border-t pt-4"
                    style="border-color: var(--wr-border);"
                >
                    <span class="wr-muted text-xs">
                        New in {{ $monthShortLabel }}
                    </span>

                    <span class="font-black text-lime-500">
                        +{{ number_format($merchantsInMonth) }}
                    </span>
                </div>
            </div>


            {{-- MARKETS --}}

            <a
                href="{{ route('content.markets.index') }}"
                class="wr-panel group rounded-2xl border p-6 transition hover:-translate-y-0.5"
            >
                <div class="flex items-start justify-between gap-4">

                    <div>
                        <div class="wr-muted text-sm font-semibold">
                            Markets
                        </div>

                        <div class="wr-text mt-3 text-4xl font-black">
                            {{ number_format($marketsTotal) }}
                        </div>
                    </div>

                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-xl"
                        style="
                            background: var(--wr-panel-secondary);
                            color: #a3e635;
                        "
                    >
                        <svg
                            class="h-6 w-6"
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

                <div
                    class="mt-5 flex items-center justify-between border-t pt-4"
                    style="border-color: var(--wr-border);"
                >
                    <span class="wr-muted text-xs">
                        New in {{ $monthShortLabel }}
                    </span>

                    <span class="font-black text-lime-500">
                        +{{ number_format($marketsInMonth) }}
                    </span>
                </div>
            </a>

        </div>


        {{-- ========================================================= --}}
        {{-- MONTH ACTIVITY --}}
        {{-- ========================================================= --}}

        <section class="wr-panel overflow-hidden rounded-2xl border">

            <div
                class="flex flex-col gap-2 border-b px-6 py-5 sm:flex-row sm:items-center sm:justify-between"
                style="border-color: var(--wr-border);"
            >
                <div>
                    <h3 class="wr-text text-base font-black">
                        {{ $monthLabel }} Activity
                    </h3>

                    <p class="wr-muted mt-1 text-xs">
                        Events recorded during the selected calendar month.
                    </p>
                </div>

                <div class="wr-muted text-xs font-semibold">
                    {{ $selectedMonth->format('M j') }}
                    —
                    {{ $activityEndLabel }}
                </div>
            </div>


            <div class="grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">

                {{-- NEW USERS --}}

                <div
                    class="border-b p-5 sm:border-r lg:border-b-0"
                    style="border-color: var(--wr-border);"
                >
                    <div class="wr-muted text-xs font-semibold">
                        New Users
                    </div>

                    <div class="wr-text mt-3 text-2xl font-black">
                        {{ number_format($usersInMonth) }}
                    </div>

                    <div class="wr-muted-soft mt-2 text-[11px]">
                        Registered
                    </div>
                </div>


                {{-- NEW MERCHANTS --}}

                <div
                    class="border-b p-5 lg:border-b-0 lg:border-r"
                    style="border-color: var(--wr-border);"
                >
                    <div class="wr-muted text-xs font-semibold">
                        New Merchants
                    </div>

                    <div class="wr-text mt-3 text-2xl font-black">
                        {{ number_format($merchantsInMonth) }}
                    </div>

                    <div class="wr-muted-soft mt-2 text-[11px]">
                        Created
                    </div>
                </div>


                {{-- CREATED --}}

                <div
                    class="border-b p-5 sm:border-r lg:border-b-0 lg:border-r"
                    style="border-color: var(--wr-border);"
                >
                    <div class="wr-muted text-xs font-semibold">
                        Markets Created
                    </div>

                    <div class="wr-text mt-3 text-2xl font-black">
                        {{ number_format($marketsInMonth) }}
                    </div>

                    <div class="wr-muted-soft mt-2 text-[11px]">
                        New content
                    </div>
                </div>


                {{-- APPROVED --}}

                <div
                    class="border-b p-5 lg:border-b-0 lg:border-r"
                    style="border-color: var(--wr-border);"
                >
                    <div class="wr-muted text-xs font-semibold">
                        Approved
                    </div>

                    <div class="mt-3 text-2xl font-black text-blue-500">
                        {{ number_format($marketsApprovedInMonth) }}
                    </div>

                    <div class="wr-muted-soft mt-2 text-[11px]">
                        Approved
                    </div>
                </div>


                {{-- PUBLISHED --}}

                <div
                    class="border-b p-5 sm:border-b-0 sm:border-r"
                    style="border-color: var(--wr-border);"
                >
                    <div class="wr-muted text-xs font-semibold">
                        Published
                    </div>

                    <div class="mt-3 text-2xl font-black text-lime-500">
                        {{ number_format($marketsPublishedInMonth) }}
                    </div>

                    <div class="wr-muted-soft mt-2 text-[11px]">
                        Went live
                    </div>
                </div>


                {{-- RESOLVED --}}

                <div class="p-5">

                    <div class="wr-muted text-xs font-semibold">
                        Resolved
                    </div>

                    <div class="mt-3 text-2xl font-black text-emerald-500">
                        {{ number_format($marketsResolvedInMonth) }}
                    </div>

                    <div class="wr-muted-soft mt-2 text-[11px]">
                        Settled
                    </div>

                </div>

            </div>


            {{-- REJECTED --}}

            <div
                class="flex items-center justify-between border-t px-6 py-3"
                style="
                    border-color: var(--wr-border);
                    background: var(--wr-panel-secondary);
                "
            >
                <span class="wr-muted text-xs">
                    Rejected during {{ $monthLabel }}
                </span>

                <span
                    class="text-sm font-black {{ $marketsRejectedInMonth > 0 ? 'text-red-500' : 'wr-muted' }}"
                >
                    {{ number_format($marketsRejectedInMonth) }}
                </span>
            </div>

        </section>


        {{-- ========================================================= --}}
        {{-- BREAKDOWNS --}}
        {{-- ========================================================= --}}

        <div class="grid gap-6 xl:grid-cols-2">

            {{-- USERS BY ROLE --}}

            <section class="wr-panel overflow-hidden rounded-2xl border">

                <div
                    class="border-b px-6 py-5"
                    style="border-color: var(--wr-border);"
                >
                    <h3 class="wr-text text-base font-black">
                        Users by role
                    </h3>

                    <p class="wr-muted mt-1 text-xs">
                        Total accounts and new accounts during {{ $monthLabel }}.
                    </p>
                </div>


                <div>

                    @foreach(\App\Enums\UserRole::cases() as $role)

                        @php
                            $roleTotal = (int) (
                                $userRoleTotals[$role->value] ?? 0
                            );

                            $roleMonth = (int) (
                                $userRoleMonth[$role->value] ?? 0
                            );
                        @endphp

                        <div
                            class="flex items-center justify-between gap-5 border-b px-6 py-4 last:border-b-0"
                            style="border-color: var(--wr-border);"
                        >

                            <div>
                                <div class="wr-text font-semibold">
                                    {{ $role->label() }}
                                </div>

                                <div class="wr-muted mt-1 text-xs">

                                    @if($roleMonth > 0)

                                        <span class="font-bold text-lime-500">
                                            +{{ number_format($roleMonth) }}
                                        </span>

                                    @else

                                        0

                                    @endif

                                    in {{ $monthShortLabel }}
                                </div>
                            </div>


                            <div class="text-right">

                                <div class="wr-text text-xl font-black">
                                    {{ number_format($roleTotal) }}
                                </div>

                                <div class="wr-muted-soft text-[10px] font-bold uppercase tracking-[0.12em]">
                                    Total
                                </div>

                            </div>

                        </div>

                    @endforeach

                </div>

            </section>


            {{-- MARKETS BY STATUS --}}

            <section class="wr-panel overflow-hidden rounded-2xl border">

                <div
                    class="border-b px-6 py-5"
                    style="border-color: var(--wr-border);"
                >
                    <h3 class="wr-text text-base font-black">
                        Markets by status
                    </h3>

                    <p class="wr-muted mt-1 text-xs">
                        Total markets and markets created during {{ $monthLabel }} by their current status.
                    </p>
                </div>


                <div>

                    @foreach(\App\Enums\BetStatus::cases() as $status)

                        @php
                            $statusTotal = (int) (
                                $marketStatusTotals[$status->value] ?? 0
                            );

                            $statusMonth = (int) (
                                $marketStatusMonth[$status->value] ?? 0
                            );
                        @endphp

                        <div
                            class="flex items-center justify-between gap-5 border-b px-6 py-4 last:border-b-0"
                            style="border-color: var(--wr-border);"
                        >

                            <div class="flex items-center gap-3">

                                <span
                                    class="h-2.5 w-2.5 shrink-0 rounded-full
                                    {{
                                        match($status) {
                                            \App\Enums\BetStatus::DRAFT
                                                => 'bg-slate-400',

                                            \App\Enums\BetStatus::PENDING_REVIEW
                                                => 'bg-amber-400',

                                            \App\Enums\BetStatus::APPROVED
                                                => 'bg-blue-400',

                                            \App\Enums\BetStatus::REJECTED
                                                => 'bg-red-400',

                                            \App\Enums\BetStatus::PUBLISHED
                                                => 'bg-lime-400',

                                            \App\Enums\BetStatus::RESOLVING
                                                => 'bg-violet-400',

                                            \App\Enums\BetStatus::RESOLVED
                                                => 'bg-emerald-400',

                                            \App\Enums\BetStatus::CANCELLED
                                                => 'bg-slate-500',
                                        }
                                    }}"
                                ></span>


                                <div>

                                    <div class="wr-text font-semibold">
                                        {{ $status->label() }}
                                    </div>

                                    <div class="wr-muted mt-1 text-xs">

                                        @if($statusMonth > 0)

                                            <span class="font-bold text-lime-500">
                                                +{{ number_format($statusMonth) }}
                                            </span>

                                        @else

                                            0

                                        @endif

                                        created in {{ $monthShortLabel }}
                                    </div>

                                </div>

                            </div>


                            <div class="text-right">

                                <div class="wr-text text-xl font-black">
                                    {{ number_format($statusTotal) }}
                                </div>

                                <div class="wr-muted-soft text-[10px] font-bold uppercase tracking-[0.12em]">
                                    Total
                                </div>

                            </div>

                        </div>

                    @endforeach

                </div>

            </section>

        </div>

    </div>

@endsection
