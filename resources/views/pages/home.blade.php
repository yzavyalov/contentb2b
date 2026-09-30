@extends('layouts.public')

@section('title', 'wrangle.win — Prediction Market Content Infrastructure')
@section('description', 'Launch prediction markets on your platform. We create professional markets, deliver them via API, monitor results and send settlement callbacks.')

@section('content')
<div class="min-h-screen overflow-x-hidden">
    {{-- HERO --}}
    <section class="relative overflow-hidden bg-[#07111f] text-white">
        <div class="pointer-events-none absolute inset-0 opacity-70">
            <div class="absolute -left-24 top-24 h-80 w-80 rounded-full bg-emerald-500/20 blur-3xl"></div>
            <div class="absolute right-0 top-0 h-[520px] w-[520px] rounded-full bg-blue-600/20 blur-3xl"></div>
            <div class="absolute bottom-0 left-1/3 h-64 w-96 rounded-full bg-violet-700/20 blur-3xl"></div>
        </div>

        <div class="relative mx-auto max-w-7xl px-5 sm:px-8">


            <div id="mobile-menu" class="hidden border-t border-white/10 pb-5 lg:hidden">
                <div class="grid gap-2 pt-4 text-sm font-semibold text-slate-300">
                    <a href="#product" class="rounded-lg px-3 py-2 hover:bg-white/5">Product</a>
                    <a href="#categories" class="rounded-lg px-3 py-2 hover:bg-white/5">Categories</a>
                    <a href="#api" class="rounded-lg px-3 py-2 hover:bg-white/5">API</a>
                    <a href="#pricing" class="rounded-lg px-3 py-2 hover:bg-white/5">Pricing</a>
                    <a href="#docs" class="rounded-lg px-3 py-2 hover:bg-white/5">Docs</a>
                    <a href="#company" class="rounded-lg px-3 py-2 hover:bg-white/5">Company</a>
                </div>
            </div>

            <div class="grid items-center gap-12 pb-16 pt-12 lg:grid-cols-[0.95fr_1.05fr] lg:pb-20 lg:pt-16">
                <div>
                    <div class="inline-flex rounded-full border border-lime-400/30 bg-lime-400/10 px-4 py-2 text-xs font-black uppercase tracking-[0.12em] text-lime-300">
                        Prediction Market Content Infrastructure
                    </div>

                    <h1 class="mt-6 max-w-2xl text-5xl font-black leading-[0.98] tracking-tight sm:text-6xl xl:text-7xl">
                        Launch Prediction Markets.
                        <span class="mt-2 block bg-gradient-to-r from-lime-300 via-emerald-300 to-cyan-300 bg-clip-text text-transparent">
                            We Handle the Rest.
                        </span>
                    </h1>

                    <p class="mt-7 max-w-xl text-lg leading-8 text-slate-300">
                        We create professional prediction markets in multiple categories and languages,
                        deliver them via API, monitor real-world events and send settlement results to your platform via callback.
                    </p>

                    <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                        <a href="#api" class="inline-flex items-center justify-center rounded-xl bg-lime-400 px-6 py-4 text-sm font-black text-slate-950 transition hover:bg-lime-300">
                            Get API Access
                        </a>

                        <a href="https://example.com" target="_blank"
                           class="inline-flex items-center justify-center gap-2 rounded-xl border border-white/20 px-6 py-4 text-sm font-black text-white transition hover:bg-white/5">
                            View Sample Markets
                            <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 5h5v5M10 14L19 5M19 13v6H5V5h6"/>
                            </svg>
                        </a>

                        <a href="#demo" class="inline-flex items-center justify-center rounded-xl border border-white/20 px-6 py-4 text-sm font-black text-white transition hover:bg-white/5">
                            Request a Demo
                        </a>
                    </div>

                    <div class="mt-10 grid grid-cols-2 gap-5 border-t border-white/10 pt-8 sm:grid-cols-4">
                        @foreach ([
                            ['100K+', 'Active Markets'],
                            ['30+', 'Languages'],
                            ['< 1 sec', 'Avg. Callback'],
                            ['99.9%', 'Uptime'],
                        ] as [$value, $label])
                            <div>
                                <div class="text-2xl font-black text-lime-300">{{ $value }}</div>
                                <div class="mt-1 text-xs font-semibold text-slate-400">{{ $label }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Live markets preview --}}
                <div class="relative">
                    <div class="absolute -inset-6 rounded-[2rem] bg-emerald-500/10 blur-3xl"></div>
                    <div class="relative overflow-hidden rounded-[1.75rem] border border-white/10 bg-[#0b1727]/95 shadow-2xl">
                        <div class="flex items-center justify-between border-b border-white/10 px-5 py-4 sm:px-6">
                            <h2 class="text-lg font-black">Live Markets Preview</h2>
                            <span class="inline-flex items-center gap-2 text-xs font-bold text-lime-300">
                                <span class="h-2 w-2 rounded-full bg-lime-400"></span> Live
                            </span>
                        </div>

                        <div class="divide-y divide-white/10">
                            @foreach ([
                                ['⚽', 'SPORTS', 'Will Real Madrid win the Champions League?', '1.72', '2.05'],
                                ['₿', 'CRYPTO', 'Will Bitcoin be above $100,000 by Dec 31?', '1.85', '2.95'],
                                ['🏛️', 'POLITICS', 'Will the Republican candidate win the US election?', '1.68', '2.20'],
                                ['🎬', 'ENTERTAINMENT', 'Will Oppenheimer win Best Picture at the Oscars?', '1.70', '2.10'],
                            ] as [$icon, $tag, $question, $yes, $no])
                                <div class="grid grid-cols-[44px_1fr] gap-3 px-4 py-4 sm:grid-cols-[52px_1fr_78px_78px] sm:items-center sm:gap-4 sm:px-6">
                                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-white/5 text-2xl sm:h-12 sm:w-12">
                                        {{ $icon }}
                                    </div>

                                    <div>
                                        <span class="rounded-md bg-violet-500/15 px-2 py-1 text-[10px] font-black text-violet-300">{{ $tag }}</span>
                                        <p class="mt-2 text-sm font-bold leading-snug text-white">{{ $question }}</p>

                                        <div class="mt-3 grid grid-cols-2 gap-2 sm:hidden">
                                            <div class="rounded-lg border border-emerald-400/25 bg-emerald-400/5 px-3 py-2 text-center">
                                                <span class="block text-[10px] text-slate-400">Yes</span>
                                                <span class="font-black text-lime-300">{{ $yes }}</span>
                                            </div>
                                            <div class="rounded-lg border border-rose-400/25 bg-rose-400/5 px-3 py-2 text-center">
                                                <span class="block text-[10px] text-slate-400">No</span>
                                                <span class="font-black text-rose-400">{{ $no }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="hidden rounded-lg border border-emerald-400/25 bg-emerald-400/5 px-3 py-2 text-center sm:block">
                                        <span class="block text-[10px] text-slate-400">Yes</span>
                                        <span class="font-black text-lime-300">{{ $yes }}</span>
                                    </div>

                                    <div class="hidden rounded-lg border border-rose-400/25 bg-rose-400/5 px-3 py-2 text-center sm:block">
                                        <span class="block text-[10px] text-slate-400">No</span>
                                        <span class="font-black text-rose-400">{{ $no }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <a href="https://example.com" target="_blank"
                           class="flex items-center justify-center gap-2 border-t border-white/10 px-5 py-5 text-sm font-black text-cyan-300 transition hover:bg-white/[0.03]">
                            Explore all markets on our sample platform
                            <span>→</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- HOW IT WORKS --}}
    <section id="product" class="bg-white py-16 sm:py-20">
        <div class="mx-auto max-w-7xl px-5 sm:px-8">
            <div class="grid gap-10 lg:grid-cols-[1fr_0.55fr]">
                <div>
                    <div class="text-center lg:text-left">
                        <p class="text-sm font-black uppercase tracking-[0.16em] text-emerald-600">Workflow</p>
                        <h2 class="mt-3 text-3xl font-black tracking-tight sm:text-4xl">How It Works</h2>
                    </div>

                    <div class="mt-10 grid gap-7 sm:grid-cols-2 xl:grid-cols-5">
                        @foreach ([
                            ['01', 'We Create', 'Professional settlement-ready markets across categories and languages.'],
                            ['02', 'API Delivery', 'Receive markets through API in the language and categories you need.'],
                            ['03', 'You Go Live', 'Publish them and let users place predictions on your platform.'],
                            ['04', 'We Monitor', 'We track the underlying event until the outcome is determined.'],
                            ['05', 'You Get Results', 'The final outcome is delivered instantly via callback.'],
                        ] as [$num, $title, $text])
                            <div class="relative">
                                <div class="flex h-14 w-14 items-center justify-center rounded-full border border-emerald-100 bg-emerald-50 font-black text-emerald-600">
                                    {{ $num }}
                                </div>
                                <h3 class="mt-5 font-black text-slate-950">{{ $title }}</h3>
                                <p class="mt-2 text-sm leading-6 text-slate-600">{{ $text }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-[1.5rem] bg-[#07111f] p-5 text-white shadow-xl sm:p-6">
                    <div class="text-sm font-black">Settlement Callback Example</div>
                    <pre class="mt-5 overflow-x-auto rounded-xl bg-black/20 p-4 text-xs leading-6 text-slate-300"><code>{
  "event": "market.settled",
  "market_id": "WR-184521",
  "result": "YES",
  "status": "settled",
  "settled_at": "2026-09-03T12:30:00Z"
}</code></pre>
                    <div class="mt-4 flex items-center gap-2 text-sm font-bold text-lime-300">
                        <span>⚡</span> Delivered to your platform instantly
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- CATEGORIES --}}
    <section id="categories" class="border-y border-slate-200 bg-slate-50 py-16">
        <div class="mx-auto max-w-7xl px-5 sm:px-8">
            <div class="text-center">
                <p class="text-sm font-black uppercase tracking-[0.16em] text-emerald-600">Content</p>
                <h2 class="mt-3 text-3xl font-black tracking-tight sm:text-4xl">Popular Categories</h2>
            </div>

            <div class="mt-10 grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-8">
                @foreach ([
                    ['⚽', 'Sports', '25,000+'],
                    ['₿', 'Crypto', '8,000+'],
                    ['🎮', 'Esports', '15,000+'],
                    ['🏛️', 'Politics', '4,000+'],
                    ['🎬', 'Entertainment', '6,000+'],
                    ['📈', 'Finance', '5,000+'],
                    ['🧠', 'Tech', '3,000+'],
                    ['•••', 'More', 'All categories'],
                ] as [$icon, $title, $count])
                    <div class="rounded-2xl border border-slate-200 bg-white p-4 text-center shadow-sm">
                        <div class="text-3xl">{{ $icon }}</div>
                        <div class="mt-3 text-sm font-black">{{ $title }}</div>
                        <div class="mt-1 text-xs text-slate-500">{{ $count }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- PRICING --}}
    <section id="pricing" class="bg-[#07111f] py-16 text-white sm:py-20">
        <div class="mx-auto max-w-7xl px-5 sm:px-8">
            <div class="text-center">
                <p class="text-sm font-black uppercase tracking-[0.16em] text-lime-300">Pricing</p>
                <h2 class="mt-3 text-3xl font-black tracking-tight sm:text-4xl">Simple, Transparent Pricing</h2>
                <p class="mx-auto mt-4 max-w-2xl text-slate-400">
                    Every plan includes content creation, API delivery, result monitoring and settlement callbacks.
                </p>
            </div>

            <div class="mt-10 grid gap-5 lg:grid-cols-3">
                @php
                    $plans = [
                        [
                            'name' => 'Pay As You Go',
                            'price' => '$20',
                            'suffix' => '/ market',
                            'desc' => 'For platforms using fewer than 1,000 markets per month.',
                            'accent' => 'lime',
                            'button' => 'Start with API',
                            'features' => ['$20 per delivered market', 'No monthly commitment', 'API access', 'Multiple languages', 'Result monitoring', 'Settlement callbacks'],
                        ],
                        [
                            'name' => 'Growth',
                            'price' => '$20,000',
                            'suffix' => '/ month',
                            'desc' => 'For 1,000–2,000 markets per month.',
                            'accent' => 'blue',
                            'button' => 'Choose Growth',
                            'features' => ['Up to 2,000 markets/month', 'All available categories', 'Multiple languages', 'API access', 'Result monitoring', 'Settlement callbacks', 'Priority support'],
                        ],
                        [
                            'name' => 'Unlimited',
                            'price' => '$40,000',
                            'suffix' => '/ month',
                            'desc' => 'For more than 2,000 markets per month.',
                            'accent' => 'violet',
                            'button' => 'Contact Sales',
                            'features' => ['Unlimited markets', 'All categories', 'Multiple languages', 'Full API access', 'Result monitoring', 'Settlement callbacks', 'Priority support', 'Custom market feeds'],
                        ],
                    ];
                @endphp

                @foreach ($plans as $plan)
                    @php
                        $border = match($plan['accent']) {
                            'lime' => 'border-lime-400/30',
                            'blue' => 'border-sky-400/30',
                            default => 'border-violet-400/30',
                        };
                        $button = match($plan['accent']) {
                            'lime' => 'bg-lime-400 text-slate-950 hover:bg-lime-300',
                            'blue' => 'bg-sky-500 text-white hover:bg-sky-400',
                            default => 'bg-violet-600 text-white hover:bg-violet-500',
                        };
                    @endphp

                    <div class="flex flex-col rounded-[1.5rem] border {{ $border }} bg-white/[0.04] p-6 sm:p-7">
                        <div class="text-sm font-black text-slate-300">{{ $plan['name'] }}</div>
                        <div class="mt-4 flex items-end gap-2">
                            <span class="text-4xl font-black">{{ $plan['price'] }}</span>
                            <span class="pb-1 text-sm text-slate-400">{{ $plan['suffix'] }}</span>
                        </div>
                        <p class="mt-4 min-h-12 text-sm leading-6 text-slate-400">{{ $plan['desc'] }}</p>

                        <div class="mt-6 space-y-3">
                            @foreach ($plan['features'] as $feature)
                                <div class="flex gap-3 text-sm text-slate-200">
                                    <span class="text-lime-300">✓</span>
                                    <span>{{ $feature }}</span>
                                </div>
                            @endforeach
                        </div>

                        <a href="#demo" class="mt-8 inline-flex justify-center rounded-xl px-5 py-4 text-sm font-black transition {{ $button }}">
                            {{ $plan['button'] }}
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section id="demo" class="bg-white py-14">
        <div class="mx-auto max-w-7xl px-5 sm:px-8">
            <div class="overflow-hidden rounded-[1.5rem] bg-gradient-to-r from-cyan-600 via-blue-700 to-violet-700 p-7 text-white shadow-xl sm:p-10">
                <div class="flex flex-col gap-7 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h2 class="text-3xl font-black tracking-tight">Ready to add prediction markets to your platform?</h2>
                        <p class="mt-3 text-white/80">One integration. Endless markets. Real results.</p>
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row">
                        <a href="mailto:sales@wrangle.win" class="rounded-xl bg-lime-400 px-6 py-4 text-center text-sm font-black text-slate-950 transition hover:bg-lime-300">
                            Get API Access
                        </a>
                        <a href="https://example.com" target="_blank" class="rounded-xl border border-white/25 px-6 py-4 text-center text-sm font-black text-white transition hover:bg-white/10">
                            View Sample Markets ↗
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer id="company" class="border-t border-slate-200 bg-white py-8">
        <div class="mx-auto flex max-w-7xl flex-col gap-4 px-5 text-sm text-slate-500 sm:px-8 md:flex-row md:items-center md:justify-between">
            <div class="font-black text-slate-950">wrangle<span class="text-lime-500">.win</span></div>
            <div>Prediction Market Content Infrastructure</div>
            <div>© {{ date('Y') }} wrangle.win</div>
        </div>
    </footer>
</div>
@endsection
