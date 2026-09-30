<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', $title ?? 'Dashboard') РІР‚вЂќ wrangle.win</title>

    {{-- Apply theme before CSS paints to avoid a light/dark flash --}}
    <script>
        (() => {
            const savedTheme = localStorage.getItem('wrangle-theme') || 'dark';
            const systemDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

            const resolvedTheme =
                savedTheme === 'system'
                    ? (systemDark ? 'dark' : 'light')
                    : savedTheme;

            document.documentElement.dataset.theme = resolvedTheme;
            document.documentElement.dataset.themePreference = savedTheme;
        })();
    </script>

    <style>
        :root {
            color-scheme: dark;
        }

        html[data-theme="dark"] {
            color-scheme: dark;

            --wr-page: #06111f;
            --wr-sidebar: #081523;
            --wr-panel: #0b192a;
            --wr-panel-secondary: #0a1726;
            --wr-input: #07111f;
            --wr-hover: #10233a;

            --wr-border: #1c344d;
            --wr-border-soft: rgba(255, 255, 255, 0.08);
            --wr-border-strong: #2c4965;

            --wr-text: #ffffff;
            --wr-text-soft: #cbd5e1;
            --wr-muted: #94a3b8;
            --wr-muted-soft: #64748b;

            --wr-topbar: rgba(6, 17, 31, 0.90);
            --wr-shadow: 0 18px 50px rgba(0, 0, 0, 0.10);
        }

        html[data-theme="light"] {
            color-scheme: light;

            --wr-page: #f4f7fb;
            --wr-sidebar: #ffffff;
            --wr-panel: #ffffff;
            --wr-panel-secondary: #f8fafc;
            --wr-input: #ffffff;
            --wr-hover: #f1f5f9;

            --wr-border: #dbe3ec;
            --wr-border-soft: #e8edf3;
            --wr-border-strong: #c5d0dc;

            --wr-text: #0f172a;
            --wr-text-soft: #334155;
            --wr-muted: #64748b;
            --wr-muted-soft: #94a3b8;

            --wr-topbar: rgba(244, 247, 251, 0.90);
            --wr-shadow: 0 14px 40px rgba(15, 23, 42, 0.06);
        }

        body {
            background: var(--wr-page);
            color: var(--wr-text);
        }

        .wr-sidebar {
            background: var(--wr-sidebar);
            border-color: var(--wr-border);
        }

        .wr-topbar {
            background: var(--wr-topbar);
            border-color: var(--wr-border);
        }

        .wr-panel {
            background: var(--wr-panel);
            border-color: var(--wr-border-soft);
            box-shadow: var(--wr-shadow);
        }

        .wr-text {
            color: var(--wr-text);
        }

        .wr-text-soft {
            color: var(--wr-text-soft);
        }

        .wr-muted {
            color: var(--wr-muted);
        }

        .wr-muted-soft {
            color: var(--wr-muted-soft);
        }

        .wr-nav {
            color: var(--wr-muted);
        }

        .wr-nav:hover {
            background: var(--wr-hover);
            color: var(--wr-text);
        }

        .wr-nav-active {
            background: #a3e635;
            color: #06111f;
        }

        .wr-profile-button {
            border-color: var(--wr-border-strong);
            background: var(--wr-panel);
            color: var(--wr-text-soft);
        }

        .wr-profile-button:hover {
            border-color: #84cc16;
            color: var(--wr-text);
        }

        .wr-theme-switch {
            display: inline-flex;
            align-items: center;
            padding: 4px;
            border: 1px solid var(--wr-border);
            border-radius: 12px;
            background: var(--wr-panel);
            box-shadow: var(--wr-shadow);
        }

        .wr-theme-button {
            border: 0;
            border-radius: 8px;
            padding: 7px 10px;
            background: transparent;
            color: var(--wr-muted);
            font-size: 12px;
            font-weight: 800;
            line-height: 1;
            cursor: pointer;
            transition:
                background-color .15s ease,
                color .15s ease,
                box-shadow .15s ease;
        }

        .wr-theme-button:hover {
            color: var(--wr-text);
        }

        .wr-theme-button.is-active {
            background: #a3e635;
            color: #06111f;
            box-shadow: 0 2px 8px rgba(132, 204, 22, .18);
        }

        @media (max-width: 767px) {
            .wr-theme-label {
                display: none;
            }

            .wr-theme-button {
                padding: 8px;
            }
        }
    </style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @livewireStyles
</head>

<body class="min-h-screen antialiased">

<div class="min-h-screen">

    {{-- Sidebar --}}
    <aside
        class="
            wr-sidebar
            fixed inset-y-0 left-0 z-40
            hidden w-[260px]
            border-r
            lg:block
        "
    >
        <div class="flex h-full flex-col">

            {{-- Logo --}}
            <div
                class="flex h-20 items-center border-b px-6"
                style="border-color: var(--wr-border);"
            >
                <a
                    href="{{ auth()->user()->isMerchant() ? route('merchant.dashboard') : route('dashboard') }}"
                    class="wr-text text-2xl font-black tracking-tight"
                >
                    wrangle<span class="text-lime-400">.win</span>
                </a>
            </div>


            {{-- Navigation --}}
            <nav class="flex-1 space-y-1 overflow-y-auto px-4 py-6">

                <a
                    href="{{ auth()->user()->isMerchant() ? route('merchant.dashboard') : route('dashboard') }}"
                    class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold transition {{
                        (auth()->user()->isMerchant()
                            ? request()->routeIs('merchant.dashboard')
                            : request()->routeIs('dashboard'))
                            ? 'wr-nav-active'
                            : 'wr-nav'
                    }}"
                >
                    Dashboard
                </a>

                @if(auth()->user()->isAdmin() || auth()->user()->isContentManager() || auth()->user()->isContentSupervisor())
                    <div class="wr-muted-soft px-4 pb-2 pt-5 text-[10px] font-bold uppercase tracking-[0.18em]">Content</div>
                    <a href="{{ route('content.markets.index') }}" class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold transition {{ request()->routeIs('content.markets.*') ? 'wr-nav-active' : 'wr-nav' }}">Markets</a>
                @endif

                @if(auth()->user()->isAdmin())

                    @php
                        $newAccountApplicationsCount =
                            \App\Models\AccountApplication::query()
                                ->where(
                                    'status',
                                    \App\Enums\AccountApplicationStatus::NEW->value
                                )
                                ->count();
                    @endphp

                    <div class="wr-muted-soft px-4 pb-2 pt-5 text-[10px] font-bold uppercase tracking-[0.18em]">
                        Administration
                    </div>

                    {{-- Users --}}
                    <a
                        href="{{ route('admin.users.index') }}"
                        class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold transition
            {{ request()->routeIs('admin.users.*') ? 'wr-nav-active' : 'wr-nav' }}"
                    >
        <span class="flex-1">
            Users
        </span>
                    </a>

                    {{-- Account Applications --}}
                    <a
                        href="{{ route('admin.account-applications') }}"
                        class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold transition
            {{ request()->routeIs('admin.account-applications') ? 'wr-nav-active' : 'wr-nav' }}"
                    >
        <span class="flex-1">
            Applications
        </span>

                        @if($newAccountApplicationsCount > 0)
                            <span
                                class="inline-flex min-w-[22px] items-center justify-center rounded-full bg-lime-400 px-1.5 py-0.5 text-[10px] font-black text-[#07111f]"
                                title="{{ $newAccountApplicationsCount }} new applications"
                            >
                {{ $newAccountApplicationsCount > 99
                    ? '99+'
                    : $newAccountApplicationsCount }}
            </span>
                        @endif
                    </a>

                    {{-- Pages --}}
                    <a
                        href="{{ route('admin.pages.index') }}"
                        class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold transition
            {{ request()->routeIs('admin.pages.*') ? 'wr-nav-active' : 'wr-nav' }}"
                    >
        <span class="flex-1">
            Pages
        </span>
                    </a>

                @endif

                @if(auth()->user()->isAdmin() || auth()->user()->isMerchant())
                    <div class="wr-muted-soft px-4 pb-2 pt-5 text-[10px] font-bold uppercase tracking-[0.18em]">
                        Merchant
                    </div>

                    @if(auth()->user()->isMerchant())
                        @php
                            $merchantOptions = auth()->user()
                                ->merchants()
                                ->orderBy('name')
                                ->orderBy('id')
                                ->get();

                            /*
                             * null means that the user is currently viewing
                             * the aggregated All Merchants context.
                             */
                            $selectedMerchant = $currentMerchant ?? null;
                            $isAllMerchants = $selectedMerchant === null;
                        @endphp

                        @if($merchantOptions->isNotEmpty())
                            <div
                                class="mb-3 rounded-xl border p-3"
                                style="border-color: var(--wr-border); background: var(--wr-panel-secondary);"
                            >
                                <div class="wr-muted-soft mb-2 text-[10px] font-bold uppercase tracking-[0.15em]">
                                    Merchant context
                                </div>

                                <form
                                    method="POST"
                                    action="{{ route('merchant.switch-all') }}"
                                    data-merchant-switch-form
                                >
                                    @csrf

                                    <select
                                        class="w-full cursor-pointer rounded-xl border px-3 py-2.5 text-sm font-bold outline-none transition focus:border-lime-400"
                                        style="border-color: var(--wr-border); background: var(--wr-input); color: var(--wr-text);"
                                        data-merchant-switch-select
                                        aria-label="Merchant context"
                                    >
                                        <option
                                            value="{{ route('merchant.switch-all') }}"
                                            @selected($isAllMerchants)
                                        >
                                            All Merchants
                                        </option>

                                        @foreach($merchantOptions as $merchantOption)
                                            <option
                                                value="{{ route('merchant.switch', $merchantOption) }}"
                                                @selected((int) ($selectedMerchant?->id ?? 0) === (int) $merchantOption->id)
                                            >
                                                {{ $merchantOption->name }} (#{{ $merchantOption->id }})
                                            </option>
                                        @endforeach
                                    </select>

                                    <div class="wr-muted mt-2 text-[11px] leading-4">
                                        @if($isAllMerchants)
                                            Showing combined data from all your merchant accounts.
                                        @else
                                            Markets, API tokens and billing use {{ $selectedMerchant->name }}.
                                        @endif
                                    </div>
                                </form>
                            </div>
                        @else
                            <div
                                class="mb-3 rounded-xl border px-3 py-3 text-xs"
                                style="border-color: var(--wr-border); background: var(--wr-panel-secondary); color: var(--wr-muted);"
                            >
                                No merchant accounts are assigned to this user.
                            </div>
                        @endif
                    @endif

                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('merchant.dashboard') }}"
                           class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold transition {{ request()->routeIs('merchant.dashboard') ? 'wr-nav-active' : 'wr-nav' }}">
                            Dashboard
                        </a>
                    @endif

                    <a href="{{ route('merchant.markets') }}" class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold transition {{ request()->routeIs('merchant.markets') ? 'wr-nav-active' : 'wr-nav' }}">Markets</a>
                    <a href="{{ route('merchant.api-tokens') }}" class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold transition {{ request()->routeIs('merchant.api-tokens') ? 'wr-nav-active' : 'wr-nav' }}">API Tokens</a>
                    <a href="{{ route('merchant.webhooks') }}" class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold transition {{ request()->routeIs('merchant.webhooks') ? 'wr-nav-active' : 'wr-nav' }}">Webhooks</a>
                    <a href="{{ route('merchant.auto-delivery') }}" class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold transition {{ request()->routeIs('merchant.auto-delivery') ? 'wr-nav-active' : 'wr-nav' }}">Auto Delivery</a>
                    <a href="{{ route('merchant.billing') }}" class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold transition {{ request()->routeIs('merchant.billing') ? 'wr-nav-active' : 'wr-nav' }}">Billing</a>
                @endif

                @if(auth()->user()->isAdmin() || auth()->user()->isFinancialManager())
                    <div class="wr-muted-soft px-4 pb-2 pt-5 text-[10px] font-bold uppercase tracking-[0.18em]">Finance</div>
                    <a href="{{ route('finance.dashboard') }}" class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold transition {{ request()->routeIs('finance.dashboard') ? 'wr-nav-active' : 'wr-nav' }}">Dashboard</a>
                    <a
                        href="{{ route('finance.merchants') }}"
                        class="
                                    flex items-center gap-3 rounded-xl px-4 py-3
                                    text-sm font-semibold transition
                                    {{ request()->routeIs('finance.merchants')
                                        ? 'wr-nav-active'
                                        : 'wr-nav' }}
                                "
                    >
                        Merchants
                    </a>

                    <a
                        href="{{ route('finance.billing-plans') }}"
                        class="
                                    flex items-center gap-3 rounded-xl px-4 py-3
                                    text-sm font-semibold transition
                                    {{ request()->routeIs('finance.billing-plans')
                                        ? 'wr-nav-active'
                                        : 'wr-nav' }}
                                "
                    >
                        Billing Plans
                    </a>

                    <a
                        href="{{ route('finance.transactions') }}"
                        class="
                                    flex items-center gap-3 rounded-xl px-4 py-3
                                    text-sm font-semibold transition
                                    {{ request()->routeIs('finance.transactions')
                                        ? 'wr-nav-active'
                                        : 'wr-nav' }}
                                "
                    >
                        Transactions
                    </a>
                @endif

            </nav>


            {{-- User block --}}
            <div
                class="border-t p-4"
                style="border-color: var(--wr-border);"
            >
                <div class="wr-panel rounded-xl border p-4">

                    <div class="wr-text truncate text-sm font-bold">
                        {{ auth()->user()->name }}
                    </div>

                    <div class="wr-muted mt-1 truncate text-xs">
                        {{ auth()->user()->email }}
                    </div>

                    <div class="mt-3 text-[10px] font-bold uppercase tracking-[0.15em] text-lime-500">
                        {{ auth()->user()->role->label() }}
                    </div>

                </div>
            </div>

        </div>
    </aside>


    {{-- Main --}}
    <div class="lg:pl-[260px]">

        {{-- Topbar --}}
        <header
            class="
                wr-topbar
                sticky top-0 z-30
                flex h-20 items-center justify-between
                border-b
                px-5
                backdrop-blur-xl
                lg:px-8
            "
        >

            <div class="min-w-0">
                <h1 class="wr-text truncate text-lg font-black">
                    @yield('title', $title ?? 'Dashboard')
                </h1>
            </div>


            <div class="flex items-center gap-2 sm:gap-3">

                {{-- Theme switch --}}
                <div
                    class="wr-theme-switch"
                    role="group"
                    aria-label="Color theme"
                >
                    {{-- LIGHT --}}
                    <button
                        type="button"
                        class="wr-theme-button inline-flex items-center"
                        data-theme-option="light"
                        title="Light theme"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            class="h-4 w-4"
                            aria-hidden="true"
                        >
                            <circle cx="12" cy="12" r="4"/>
                            <path d="M12 2v2"/>
                            <path d="M12 20v2"/>
                            <path d="m4.93 4.93 1.41 1.41"/>
                            <path d="m17.66 17.66 1.41 1.41"/>
                            <path d="M2 12h2"/>
                            <path d="M20 12h2"/>
                            <path d="m6.34 17.66-1.41 1.41"/>
                            <path d="m19.07 4.93-1.41 1.41"/>
                        </svg>

                        <span class="wr-theme-label ml-1">
            Light
        </span>
                    </button>

                    {{-- DARK --}}
                    <button
                        type="button"
                        class="wr-theme-button inline-flex items-center"
                        data-theme-option="dark"
                        title="Dark theme"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            class="h-4 w-4"
                            aria-hidden="true"
                        >
                            <path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/>
                        </svg>

                        <span class="wr-theme-label ml-1">
            Dark
        </span>
                    </button>

                    {{-- SYSTEM --}}
                    <button
                        type="button"
                        class="wr-theme-button inline-flex items-center"
                        data-theme-option="system"
                        title="Use system theme"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            class="h-4 w-4"
                            aria-hidden="true"
                        >
                            <rect
                                width="20"
                                height="14"
                                x="2"
                                y="3"
                                rx="2"
                            />
                            <line x1="8" x2="16" y1="21" y2="21"/>
                            <line x1="12" x2="12" y1="17" y2="21"/>
                        </svg>

                        <span class="wr-theme-label ml-1">
            System
        </span>
                    </button>
                </div>


                <a
                    href="{{ route('profile.edit') }}"
                    class="
                        wr-profile-button
                        rounded-xl
                        border
                        px-4 py-2.5
                        text-sm font-semibold
                        transition
                    "
                >
                    Profile
                </a>

                <form
                    method="POST"
                    action="{{ route('logout') }}"
                >
                    @csrf

                    <button
                        type="submit"
                        class="
                            rounded-xl
                            bg-lime-400
                            px-4 py-2.5
                            text-sm font-black
                            text-[#06111f]
                            transition
                            hover:bg-lime-300
                        "
                    >
                        Sign out
                    </button>
                </form>

            </div>

        </header>


        {{-- Page content --}}
        <main
            class="min-h-[calc(100vh-80px)] p-5 lg:p-8"
            style="background: var(--wr-page);"
        >

            @if(session('status'))
                <div class="mb-6 rounded-2xl border border-lime-500/20 bg-lime-500/10 px-5 py-4 text-sm font-bold text-lime-500">
                    {{ session('status') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-6 rounded-2xl border border-red-500/20 bg-red-500/10 px-5 py-4 text-sm font-bold text-red-500">
                    {{ session('error') }}
                </div>
            @endif

            {{ $slot ?? '' }}

            @yield('content')

        </main>

    </div>

</div>

<script>
    (() => {
        const bindMerchantSwitchers = () => {
            document.querySelectorAll('[data-merchant-switch-select]').forEach((select) => {
                if (select.dataset.bound === '1') {
                    return;
                }

                select.dataset.bound = '1';

                select.addEventListener('change', () => {
                    const form = select.closest('[data-merchant-switch-form]');

                    if (! form || ! select.value) {
                        return;
                    }

                    form.action = select.value;
                    form.submit();
                });
            });
        };

        bindMerchantSwitchers();
        document.addEventListener('livewire:navigated', bindMerchantSwitchers);
    })();
</script>

<script>
    (() => {
        const storageKey = 'wrangle-theme';
        const media = window.matchMedia('(prefers-color-scheme: dark)');

        const resolveTheme = (preference) => {
            if (preference === 'system') {
                return media.matches ? 'dark' : 'light';
            }

            return preference === 'light' ? 'light' : 'dark';
        };

        const applyTheme = (preference) => {
            const validPreference = ['light', 'dark', 'system'].includes(preference)
                ? preference
                : 'dark';

            const resolvedTheme = resolveTheme(validPreference);

            document.documentElement.dataset.theme = resolvedTheme;
            document.documentElement.dataset.themePreference = validPreference;

            document
                .querySelectorAll('[data-theme-option]')
                .forEach((button) => {
                    const isActive =
                        button.dataset.themeOption === validPreference;

                    button.classList.toggle('is-active', isActive);
                    button.setAttribute(
                        'aria-pressed',
                        isActive ? 'true' : 'false'
                    );
                });
        };

        const savedTheme = localStorage.getItem(storageKey) || 'dark';

        applyTheme(savedTheme);

        document.addEventListener('click', (event) => {
            const button = event.target.closest('[data-theme-option]');

            if (! button) {
                return;
            }

            const preference = button.dataset.themeOption;

            localStorage.setItem(storageKey, preference);

            applyTheme(preference);
        });

        media.addEventListener('change', () => {
            const preference =
                localStorage.getItem(storageKey) || 'dark';

            if (preference === 'system') {
                applyTheme('system');
            }
        });

        document.addEventListener('livewire:navigated', () => {
            applyTheme(
                localStorage.getItem(storageKey) || 'dark'
            );
        });
    })();
</script>

@livewireScripts

</body>
</html>
