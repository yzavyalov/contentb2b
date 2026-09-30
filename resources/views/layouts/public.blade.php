<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>

    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>@yield('title', 'wrangle.win')</title>

    @hasSection('meta_description')
        <meta
            name="description"
            content="@yield('meta_description')"
        >
    @endif

    @vite([
        'resources/css/app.css',
        'resources/js/app.js'
    ])

    @livewireStyles

</head>

<body class="min-h-screen bg-[#07111f] text-white antialiased">

<header
    class="sticky top-0 z-50 border-b border-white/10 bg-[#07111f]/95 backdrop-blur-xl"
>

    <div
        class="mx-auto flex h-20 max-w-7xl items-center justify-between px-6"
    >

        <a
            href="{{ route('home') }}"
            class="flex items-center"
        >
            <span class="text-2xl font-black tracking-tight text-white">
                wrangle<span class="text-lime-400">.win</span>
            </span>
        </a>


        <nav
            class="hidden items-center gap-7 lg:flex"
        >

            <div class="group relative">

                <a
                    href="{{ url('/product') }}"
                    class="
                        flex items-center gap-1
                        text-sm font-bold
                        transition
                        {{ request()->is('product')
                            || request()->is('how-it-works')
                            || request()->is('for-operators')
                            || request()->is('for-content-manager')
                                ? 'text-lime-400'
                                : 'text-slate-300 hover:text-white'
                        }}
                    "
                >
                    Product

                    <svg
                        class="h-4 w-4 transition group-hover:rotate-180"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                    >
                        <path
                            fill-rule="evenodd"
                            d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.51a.75.75 0 01-1.08 0l-4.25-4.51a.75.75 0 01.02-1.06z"
                            clip-rule="evenodd"
                        />
                    </svg>
                </a>


                <div
                    class="
                        invisible absolute left-0 top-full
                        w-72 pt-5
                        opacity-0
                        transition
                        group-hover:visible
                        group-hover:opacity-100
                    "
                >

                    <div
                        class="rounded-2xl border border-white/10 bg-[#0b192a] p-3 shadow-2xl shadow-black/40"
                    >

                        <a
                            href="{{ url('/product') }}"
                            class="block rounded-xl px-4 py-3 transition hover:bg-[#10233a]"
                        >
                            <div class="font-bold text-white">
                                Product Overview
                            </div>

                            <div class="mt-1 text-xs leading-5 text-slate-500">
                                Prediction market content infrastructure
                            </div>
                        </a>


                        <a
                            href="{{ url('/how-it-works') }}"
                            class="block rounded-xl px-4 py-3 transition hover:bg-[#10233a]"
                        >
                            <div class="font-bold text-white">
                                How It Works
                            </div>

                            <div class="mt-1 text-xs leading-5 text-slate-500">
                                From market creation to verified resolution
                            </div>
                        </a>


                        <a
                            href="{{ url('/for-operators') }}"
                            class="block rounded-xl px-4 py-3 transition hover:bg-[#10233a]"
                        >
                            <div class="font-bold text-white">
                                For Operators
                            </div>

                            <div class="mt-1 text-xs leading-5 text-slate-500">
                                Add prediction markets to your platform
                            </div>
                        </a>


                        <a
                            href="{{ url('/for-content-manager') }}"
                            class="block rounded-xl px-4 py-3 transition hover:bg-[#10233a]"
                        >
                            <div class="font-bold text-white">
                                For Content Managers
                            </div>

                            <div class="mt-1 text-xs leading-5 text-slate-500">
                                Scale content without scaling headcount
                            </div>
                        </a>

                    </div>

                </div>

            </div>


            <a
                href="{{ url('/content') }}"
                class="
                    text-sm font-bold transition
                    {{ request()->is('content')
                        ? 'text-lime-400'
                        : 'text-slate-300 hover:text-white'
                    }}
                "
            >
                Content
            </a>


            <a
                href="{{ url('/api') }}"
                class="
                    text-sm font-bold transition
                    {{ request()->is('api*')
                        ? 'text-lime-400'
                        : 'text-slate-300 hover:text-white'
                    }}
                "
            >
                API
            </a>


            <a
                href="{{ url('/pricing') }}"
                class="
                    text-sm font-bold transition
                    {{ request()->is('pricing')
                        ? 'text-lime-400'
                        : 'text-slate-300 hover:text-white'
                    }}
                "
            >
                Pricing
            </a>


            <div class="group relative">

                <a
                    href="{{ url('/docs') }}"
                    class="
                        flex items-center gap-1
                        text-sm font-bold
                        transition
                        {{ request()->is('docs*')
                            ? 'text-lime-400'
                            : 'text-slate-300 hover:text-white'
                        }}
                    "
                >
                    Docs

                    <svg
                        class="h-4 w-4 transition group-hover:rotate-180"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                    >
                        <path
                            fill-rule="evenodd"
                            d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.51a.75.75 0 01-1.08 0l-4.25-4.51a.75.75 0 01.02-1.06z"
                            clip-rule="evenodd"
                        />
                    </svg>
                </a>


                <div
                    class="
                        invisible absolute right-0 top-full
                        w-[420px] pt-5
                        opacity-0
                        transition
                        group-hover:visible
                        group-hover:opacity-100
                    "
                >

                    <div
                        class="rounded-2xl border border-white/10 bg-[#0b192a] p-3 shadow-2xl shadow-black/40"
                    >

                        <a
                            href="{{ url('/docs') }}"
                            class="block rounded-xl px-4 py-3 transition hover:bg-[#10233a]"
                        >
                            <div class="font-bold text-white">
                                Documentation
                            </div>

                            <div class="mt-1 text-xs leading-5 text-slate-500">
                                Developer documentation and integration guides
                            </div>
                        </a>


                        <a
                            href="{{ url('/docs/getting-started') }}"
                            class="block rounded-xl px-4 py-3 transition hover:bg-[#10233a]"
                        >
                            <div class="font-bold text-white">
                                Getting Started
                            </div>

                            <div class="mt-1 text-xs leading-5 text-slate-500">
                                Connect your platform to wrangle.win
                            </div>
                        </a>


                        <div class="my-2 border-t border-white/10"></div>


                        <div class="px-4 pb-2 pt-2 text-[10px] font-black uppercase tracking-[0.16em] text-slate-600">
                            API
                        </div>


                        <div class="grid grid-cols-2 gap-1">

                            <a
                                href="{{ url('/docs/api') }}"
                                class="rounded-xl px-4 py-3 transition hover:bg-[#10233a]"
                            >
                                <div class="text-sm font-bold text-white">
                                    API Reference
                                </div>

                                <div class="mt-1 text-xs text-slate-500">
                                    API architecture
                                </div>
                            </a>


                            <a
                                href="{{ url('/docs/api/authentication') }}"
                                class="rounded-xl px-4 py-3 transition hover:bg-[#10233a]"
                            >
                                <div class="text-sm font-bold text-white">
                                    Authentication
                                </div>

                                <div class="mt-1 text-xs text-slate-500">
                                    API credentials
                                </div>
                            </a>


                            <a
                                href="{{ url('/docs/api/market-delivery') }}"
                                class="rounded-xl px-4 py-3 transition hover:bg-[#10233a]"
                            >
                                <div class="text-sm font-bold text-white">
                                    Market Delivery
                                </div>

                                <div class="mt-1 text-xs text-slate-500">
                                    Receive markets
                                </div>
                            </a>


                            <a
                                href="{{ url('/docs/api/resolution-callbacks') }}"
                                class="rounded-xl px-4 py-3 transition hover:bg-[#10233a]"
                            >
                                <div class="text-sm font-bold text-white">
                                    Resolution Callbacks
                                </div>

                                <div class="mt-1 text-xs text-slate-500">
                                    Receive outcomes
                                </div>
                            </a>


                            <a
                                href="{{ url('/docs/api/errors') }}"
                                class="rounded-xl px-4 py-3 transition hover:bg-[#10233a]"
                            >
                                <div class="text-sm font-bold text-white">
                                    Errors & Retries
                                </div>

                                <div class="mt-1 text-xs text-slate-500">
                                    Failure handling
                                </div>
                            </a>


                            <a
                                href="{{ url('/docs/api/security') }}"
                                class="rounded-xl px-4 py-3 transition hover:bg-[#10233a]"
                            >
                                <div class="text-sm font-bold text-white">
                                    Security
                                </div>

                                <div class="mt-1 text-xs text-slate-500">
                                    Integration security
                                </div>
                            </a>

                        </div>


                        <div class="my-2 border-t border-white/10"></div>


                        <a
                            href="{{ url('/docs/integration-ai') }}"
                            class="block rounded-xl px-4 py-3 transition hover:bg-[#10233a]"
                        >
                            <div class="flex items-center justify-between gap-4">

                                <div>

                                    <div class="font-bold text-white">
                                        AI Integration Prompts
                                    </div>

                                    <div class="mt-1 text-xs leading-5 text-slate-500">
                                        Ready-to-use prompts for your technology stack
                                    </div>

                                </div>

                                <span
                                    class="rounded-full border border-lime-400/20 bg-lime-400/10 px-2 py-1 text-[10px] font-black uppercase tracking-wider text-lime-400"
                                >
                                    AI
                                </span>

                            </div>
                        </a>

                    </div>

                </div>

            </div>


            <a
                href="{{ url('/company') }}"
                class="
                    text-sm font-bold transition
                    {{ request()->is('company')
                        ? 'text-lime-400'
                        : 'text-slate-300 hover:text-white'
                    }}
                "
            >
                Company
            </a>

        </nav>


        <div class="hidden items-center gap-4 lg:flex">

            @auth

                <a
                    href="{{ route('dashboard') }}"
                    class="text-sm font-bold text-slate-300 transition hover:text-white"
                >
                    Dashboard
                </a>

            @else

                <a
                    href="{{ route('login') }}"
                    class="text-sm font-bold text-slate-300 transition hover:text-white"
                >
                    Log in
                </a>

            @endauth


            <a
                href="https://example.wrangle.win"
                class="
                    inline-flex items-center justify-center
                    gap-2
                    rounded-xl
                    bg-lime-400
                    px-5 py-3
                    text-sm font-black
                    text-[#07111f]
                    transition
                    hover:bg-lime-300
                "
            >
                Live Demo

                <svg
                    class="h-4 w-4"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="2"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M13.5 4.5H19.5V10.5M19 5L11 13M10 5H6.75A1.75 1.75 0 005 6.75V17.25A1.75 1.75 0 006.75 19H17.25A1.75 1.75 0 0019 17.25V14"
                    />
                </svg>
            </a>

        </div>


        <button
            type="button"
            id="mobile-menu-button"
            class="
                flex h-11 w-11 items-center justify-center
                rounded-xl
                border border-white/10
                text-white
                lg:hidden
            "
            aria-label="Open menu"
        >

            <svg
                id="mobile-menu-open-icon"
                class="h-6 w-6"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M4 6h16M4 12h16M4 18h16"
                />
            </svg>


            <svg
                id="mobile-menu-close-icon"
                class="hidden h-6 w-6"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M6 18L18 6M6 6l12 12"
                />
            </svg>

        </button>

    </div>


    <div
        id="mobile-menu"
        class="hidden border-t border-white/10 lg:hidden"
    >

        <div class="mx-auto max-w-7xl space-y-1 px-6 py-5">

            <a
                href="{{ url('/product') }}"
                class="block rounded-xl px-4 py-3 font-bold text-white hover:bg-[#10233a]"
            >
                Product
            </a>


            <a
                href="{{ url('/how-it-works') }}"
                class="block rounded-xl px-4 py-3 pl-8 text-sm font-semibold text-slate-400 hover:bg-[#10233a] hover:text-white"
            >
                How It Works
            </a>


            <a
                href="{{ url('/for-operators') }}"
                class="block rounded-xl px-4 py-3 pl-8 text-sm font-semibold text-slate-400 hover:bg-[#10233a] hover:text-white"
            >
                For Operators
            </a>


            <a
                href="{{ url('/for-content-manager') }}"
                class="block rounded-xl px-4 py-3 pl-8 text-sm font-semibold text-slate-400 hover:bg-[#10233a] hover:text-white"
            >
                For Content Managers
            </a>


            <a
                href="{{ url('/content') }}"
                class="block rounded-xl px-4 py-3 font-bold text-white hover:bg-[#10233a]"
            >
                Content
            </a>


            <a
                href="{{ url('/api') }}"
                class="block rounded-xl px-4 py-3 font-bold text-white hover:bg-[#10233a]"
            >
                API
            </a>


            <a
                href="{{ url('/pricing') }}"
                class="block rounded-xl px-4 py-3 font-bold text-white hover:bg-[#10233a]"
            >
                Pricing
            </a>


            <div class="pt-2">

                <a
                    href="{{ url('/docs') }}"
                    class="
                        flex items-center justify-between
                        rounded-xl
                        px-4 py-3
                        font-bold
                        {{ request()->is('docs*')
                            ? 'bg-lime-400/10 text-lime-400'
                            : 'text-white hover:bg-[#10233a]'
                        }}
                    "
                >
                    <span>Docs</span>

                    <span class="text-xs text-slate-500">
                        Developer
                    </span>
                </a>


                <div class="mt-1 border-l border-white/10 pl-4">

                    <a
                        href="{{ url('/docs/getting-started') }}"
                        class="block rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-400 hover:bg-[#10233a] hover:text-white"
                    >
                        Getting Started
                    </a>


                    <a
                        href="{{ url('/docs/api') }}"
                        class="block rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-400 hover:bg-[#10233a] hover:text-white"
                    >
                        API Reference
                    </a>


                    <a
                        href="{{ url('/docs/api/authentication') }}"
                        class="block rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-400 hover:bg-[#10233a] hover:text-white"
                    >
                        Authentication
                    </a>


                    <a
                        href="{{ url('/docs/api/market-delivery') }}"
                        class="block rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-400 hover:bg-[#10233a] hover:text-white"
                    >
                        Market Delivery
                    </a>


                    <a
                        href="{{ url('/docs/api/resolution-callbacks') }}"
                        class="block rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-400 hover:bg-[#10233a] hover:text-white"
                    >
                        Resolution Callbacks
                    </a>


                    <a
                        href="{{ url('/docs/api/errors') }}"
                        class="block rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-400 hover:bg-[#10233a] hover:text-white"
                    >
                        Errors & Retries
                    </a>


                    <a
                        href="{{ url('/docs/api/security') }}"
                        class="block rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-400 hover:bg-[#10233a] hover:text-white"
                    >
                        Security
                    </a>


                    <a
                        href="{{ url('/docs/integration-ai') }}"
                        class="block rounded-xl px-4 py-2.5 text-sm font-semibold text-lime-400 hover:bg-[#10233a] hover:text-lime-300"
                    >
                        AI Integration Prompts
                    </a>

                </div>

            </div>


            <a
                href="{{ url('/company') }}"
                class="block rounded-xl px-4 py-3 font-bold text-white hover:bg-[#10233a]"
            >
                Company
            </a>


            <div class="my-4 border-t border-white/10"></div>


            @auth

                <a
                    href="{{ route('dashboard') }}"
                    class="block rounded-xl px-4 py-3 font-bold text-white hover:bg-[#10233a]"
                >
                    Dashboard
                </a>

            @else

                <a
                    href="{{ route('login') }}"
                    class="block rounded-xl px-4 py-3 font-bold text-white hover:bg-[#10233a]"
                >
                    Log in
                </a>

            @endauth


            <a
                href="https://example.wrangle.win"
                class="
                    mt-3 flex items-center justify-center gap-2
                    rounded-xl
                    bg-lime-400
                    px-5 py-3
                    text-center
                    font-black
                    text-[#07111f]
                    transition
                    hover:bg-lime-300
                "
            >
                Live Demo

                <svg
                    class="h-4 w-4"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="2"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M13.5 4.5H19.5V10.5M19 5L11 13M10 5H6.75A1.75 1.75 0 005 6.75V17.25A1.75 1.75 0 006.75 19H17.25A1.75 1.75 0 0019 17.25V14"
                    />
                </svg>
            </a>

            <div class="px-4 pt-2 text-center text-xs leading-5 text-slate-500">
                Explore live prediction markets using virtual credits.
                No real money.
            </div>

        </div>

    </div>

</header>


<main>
    @yield('content')
</main>


@livewireScripts


<script>

    document.addEventListener('DOMContentLoaded', function () {

        const button = document.getElementById('mobile-menu-button');
        const menu = document.getElementById('mobile-menu');

        const openIcon = document.getElementById('mobile-menu-open-icon');
        const closeIcon = document.getElementById('mobile-menu-close-icon');

        if (!button || !menu) {
            return;
        }

        button.addEventListener('click', function () {

            menu.classList.toggle('hidden');

            openIcon.classList.toggle('hidden');
            closeIcon.classList.toggle('hidden');

        });

    });

</script>

</body>
</html>
