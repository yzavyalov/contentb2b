@extends('layouts.public')

@section('title', 'wrangle.win — Content Built for Prediction Markets')

@section('meta_description', 'Prediction market content from creation to resolution. Structured markets, 30+ languages, continuous event monitoring and verified outcomes — delivered through API.')

@section('og_title', 'wrangle.win — Content Built for Prediction Markets')

@section('og_description', 'Prediction market content from creation to resolution. Structured markets, 30+ languages, continuous event monitoring and verified outcomes — delivered through API.')

@section('og_url', url('/'))

@section('og_image', asset('images/wrangle-social-preview.png'))

@section('og_image_alt', 'wrangle.win — Content is complicated. We wrangle it.')

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

                <div class="grid items-center gap-12 pb-16 pt-12 lg:grid-cols-[0.95fr_1.05fr] lg:pb-20 lg:pt-16">

                    {{-- Hero copy --}}
                    <div>

                        <div class="inline-flex rounded-full border border-lime-400/30 bg-lime-400/10 px-4 py-2 text-xs font-black uppercase tracking-[0.12em] text-lime-300">
                            Content built for prediction markets.
                        </div>

                        <h1 class="mt-6 max-w-2xl text-5xl font-black leading-[0.98] tracking-tight sm:text-6xl xl:text-7xl">
                            CONTENT IS COMPLICATED.

                            <span class="mt-2 block bg-gradient-to-r from-lime-300 via-emerald-300 to-cyan-300 bg-clip-text text-transparent">
                                WE WRANGLE IT.
                            </span>
                        </h1>

                        <p class="mt-7 max-w-2xl text-lg leading-8 text-slate-300">
                            wrangle.win is a specialist content studio serving gambling,
                            sportsbook, casino and gaming businesses. We handle the content
                            and resolution layer end to end — from structured market creation
                            and localisation to real-world event monitoring and verified
                            resolution callbacks, all delivered through API.
                        </p>

                        <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:flex-wrap">

                            <a
                                href="#request-demo"
                                class="inline-flex items-center justify-center rounded-xl bg-lime-400 px-6 py-4 text-sm font-black text-slate-950 transition hover:bg-lime-300"
                            >
                                Request Demo
                            </a>

                            <a
                                href="{{ url('/docs/getting-started') }}"
                                class="inline-flex items-center justify-center rounded-xl border border-white/20 px-6 py-4 text-sm font-black text-white transition hover:bg-white/5"
                            >
                                API Documentation
                            </a>

                        </div>

                        {{-- Product facts --}}
                        <div class="mt-10 grid grid-cols-2 gap-5 border-t border-white/10 pt-8 sm:grid-cols-4">

                            <div>
                                <div class="text-2xl font-black text-lime-300">30+</div>
                                <div class="mt-1 text-xs font-semibold text-slate-400">
                                    Languages
                                </div>
                            </div>

                            <div>
                                <div class="text-2xl font-black text-lime-300">API</div>
                                <div class="mt-1 text-xs font-semibold text-slate-400">
                                    Content Delivery
                                </div>
                            </div>

                            <div>
                                <div class="text-2xl font-black text-lime-300">24/7</div>
                                <div class="mt-1 text-xs font-semibold text-slate-400">
                                    Event Monitoring
                                </div>
                            </div>

                            <div>
                                <div class="text-2xl font-black text-lime-300">Human</div>
                                <div class="mt-1 text-xs font-semibold text-slate-400">
                                    Verified Results
                                </div>
                            </div>

                        </div>

                    </div>


                    {{-- Example content --}}
                    <div class="relative">

                        <div class="absolute -inset-6 rounded-[2rem] bg-emerald-500/10 blur-3xl"></div>

                        <div class="relative overflow-hidden rounded-[1.75rem] border border-white/10 bg-[#0b1727]/95 shadow-2xl">

                            <div class="flex items-center justify-between border-b border-white/10 px-5 py-4 sm:px-6">

                                <div>
                                    <div class="text-xs font-bold uppercase tracking-[0.14em] text-slate-500">
                                        Example content
                                    </div>

                                    <h2 class="mt-1 text-lg font-black">
                                        Prediction Markets
                                    </h2>
                                </div>

                                <span class="inline-flex items-center gap-2 text-xs font-bold text-lime-300">
                                    <span class="h-2 w-2 rounded-full bg-lime-400"></span>
                                    API Ready
                                </span>

                            </div>


                            <div class="divide-y divide-white/10">

                                @foreach ([
                                    ['₿', 'CRYPTO', 'Will Bitcoin trade above $150,000 by the end of 2026?', 'Yes', 'No'],
                                    ['⚽', 'SPORTS', 'Will the home team win the championship final?', 'Yes', 'No'],
                                    ['🏛️', 'POLITICS', 'Will the proposed legislation pass before the deadline?', 'Yes', 'No'],
                                    ['📈', 'FINANCE', 'Will the selected index close above the target level?', 'Yes', 'No'],
                                ] as [$icon, $tag, $question, $yes, $no])

                                    <div class="grid grid-cols-[44px_1fr] gap-3 px-4 py-4 sm:grid-cols-[52px_1fr_78px_78px] sm:items-center sm:gap-4 sm:px-6">

                                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-white/5 text-2xl sm:h-12 sm:w-12">
                                            {{ $icon }}
                                        </div>

                                        <div>

                                            <span class="rounded-md bg-violet-500/15 px-2 py-1 text-[10px] font-black text-violet-300">
                                                {{ $tag }}
                                            </span>

                                            <p class="mt-2 text-sm font-bold leading-snug text-white">
                                                {{ $question }}
                                            </p>

                                            <div class="mt-3 grid grid-cols-2 gap-2 sm:hidden">

                                                <div class="rounded-lg border border-emerald-400/25 bg-emerald-400/5 px-3 py-2 text-center">
                                                    <span class="font-black text-lime-300">{{ $yes }}</span>
                                                </div>

                                                <div class="rounded-lg border border-rose-400/25 bg-rose-400/5 px-3 py-2 text-center">
                                                    <span class="font-black text-rose-400">{{ $no }}</span>
                                                </div>

                                            </div>

                                        </div>

                                        <div class="hidden rounded-lg border border-emerald-400/25 bg-emerald-400/5 px-3 py-2 text-center sm:block">
                                            <span class="font-black text-lime-300">{{ $yes }}</span>
                                        </div>

                                        <div class="hidden rounded-lg border border-rose-400/25 bg-rose-400/5 px-3 py-2 text-center sm:block">
                                            <span class="font-black text-rose-400">{{ $no }}</span>
                                        </div>

                                    </div>

                                @endforeach

                            </div>


                            <a
                                href="#request-demo"
                                class="flex items-center justify-center gap-2 border-t border-white/10 px-5 py-5 text-sm font-black text-cyan-300 transition hover:bg-white/[0.03]"
                            >
                                Request a tailored content demo
                                <span>→</span>
                            </a>

                        </div>

                    </div>

                </div>

            </div>

        </section>



        {{-- VALUE PROPOSITION --}}
        <section class="bg-white py-16 sm:py-20">

            <div class="mx-auto max-w-7xl px-5 sm:px-8">

                <div class="mx-auto max-w-4xl text-center">

                    <p class="text-sm font-black uppercase tracking-[0.16em] text-emerald-600">
                        Content that keeps up
                    </p>

                    <h2 class="mt-3 text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">
                        The gambling industry moves quickly.
                        <span class="text-emerald-600">
                            Your content needs to move with it.
                        </span>
                    </h2>

                    <p class="mx-auto mt-5 max-w-3xl text-lg leading-8 text-slate-600">
                        We help you wrangle the content, so you can focus on your users
                        and your business. Leave it to us.
                    </p>

                </div>


                <div class="mt-12 grid gap-5 md:grid-cols-2 lg:grid-cols-4">

                    @foreach ([
                        [
                            '✦',
                            'Market Creation',
                            'Structured prediction markets researched and prepared with clear outcomes and resolution conditions.'
                        ],
                        [
                            '🌍',
                            'Localised Content',
                            'Prediction market content prepared across languages and regions for the audiences you serve.'
                        ],
                        [
                            '◉',
                            'Continuous Monitoring',
                            'We monitor the underlying real-world event until the outcome can be determined.'
                        ],
                        [
                            '✓',
                            'Verified Resolution',
                            'AI-assisted outcome analysis combined with human verification before resolution delivery.'
                        ],
                    ] as [$icon, $title, $text])

                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-6">

                            <div class="text-2xl">
                                {{ $icon }}
                            </div>

                            <h3 class="mt-4 font-black text-slate-950">
                                {{ $title }}
                            </h3>

                            <p class="mt-2 text-sm leading-6 text-slate-600">
                                {{ $text }}
                            </p>

                        </div>

                    @endforeach

                </div>

            </div>

        </section>



        {{-- HOW IT WORKS --}}
        <section id="product" class="border-y border-slate-200 bg-slate-50 py-16 sm:py-20">

            <div class="mx-auto max-w-7xl px-5 sm:px-8">

                <div class="text-center">

                    <p class="text-sm font-black uppercase tracking-[0.16em] text-emerald-600">
                        How it works
                    </p>

                    <h2 class="mt-3 text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">
                        WE TAKE IT FROM HERE
                    </h2>

                    <p class="mx-auto mt-4 max-w-2xl leading-7 text-slate-600">
                        From creation to resolution, wrangle.win manages the prediction
                        market content lifecycle and delivers it directly to your platform.
                    </p>

                </div>


                <div class="mt-12 grid gap-7 sm:grid-cols-2 lg:grid-cols-5">

                    @foreach ([
                        ['01', 'Creation', 'We research, structure and prepare markets for publication and resolution.'],
                        ['02', 'API Delivery', 'Structured market content is delivered directly to your platform through API.'],
                        ['03', 'Publishing', 'Choose the markets you want and publish them within your own product experience.'],
                        ['04', 'Monitoring', 'We monitor the underlying real-world events and the sources required for resolution.'],
                        ['05', 'Resolution', 'Verified outcomes are delivered back to your platform through resolution callbacks.'],
                    ] as [$num, $title, $text])

                        <div>

                            <div class="flex h-14 w-14 items-center justify-center rounded-full border border-emerald-100 bg-emerald-50 font-black text-emerald-600">
                                {{ $num }}
                            </div>

                            <h3 class="mt-5 font-black text-slate-950">
                                {{ $title }}
                            </h3>

                            <p class="mt-2 text-sm leading-6 text-slate-600">
                                {{ $text }}
                            </p>

                        </div>

                    @endforeach

                </div>

            </div>

        </section>



        {{-- API / SEE IT IN ACTION --}}
        <section id="api" class="bg-[#07111f] py-16 text-white sm:py-20">

            <div class="mx-auto max-w-7xl px-5 sm:px-8">

                <div class="text-center">

                    <p class="text-sm font-black uppercase tracking-[0.16em] text-lime-300">
                        API Delivery
                    </p>

                    <h2 class="mt-3 text-3xl font-black tracking-tight sm:text-4xl">
                        SEE IT IN ACTION
                    </h2>

                    <p class="mx-auto mt-4 max-w-3xl leading-7 text-slate-300">
                        Receive structured prediction market content directly into your
                        platform. When the result is known, receive the verified winning
                        outcome through a resolution callback.
                    </p>

                </div>


                <div class="mt-12 grid gap-5 lg:grid-cols-2">

                    {{-- Example Request --}}
                    <div class="overflow-hidden rounded-[1.5rem] border border-white/10 bg-[#0b1727]">

                        <div class="flex items-center justify-between border-b border-white/10 px-5 py-4">

                            <div>
                                <div class="text-xs font-bold uppercase tracking-wider text-slate-500">
                                    Market Delivery
                                </div>

                                <div class="mt-1 font-black">
                                    Example Request
                                </div>
                            </div>

                            <span class="rounded-md bg-emerald-400/10 px-2 py-1 text-xs font-bold text-emerald-300">
                                JSON
                            </span>

                        </div>

                        <pre class="overflow-x-auto p-5 text-[11px] leading-5 text-slate-300"><code>{
  "event": "market.published",
  "market": {
    "id": 572,
    "status": "published",
    "source_locale": "en",
    "finish_at":
      "2026-12-31T23:59:59+00:00",
    "published_at":
      "2026-10-15T12:00:00+00:00",

    "translations": [
      {
        "locale": "en",
        "title":
          "Will Bitcoin trade above
           $150,000 by the end of 2026?",
        "description":
          "Market resolves Yes if..."
      }
    ],

    "answers": [
      {
        "id": 1141,
        "sort_order": 1,
        "translations": [
          {
            "locale": "en",
            "title": "Yes"
          }
        ]
      },
      {
        "id": 1142,
        "sort_order": 2,
        "translations": [
          {
            "locale": "en",
            "title": "No"
          }
        ]
      }
    ]
  }
}</code></pre>

                    </div>


                    {{-- Resolution Callback --}}
                    <div class="overflow-hidden rounded-[1.5rem] border border-white/10 bg-[#0b1727]">

                        <div class="flex items-center justify-between border-b border-white/10 px-5 py-4">

                            <div>
                                <div class="text-xs font-bold uppercase tracking-wider text-slate-500">
                                    Resolution Callback
                                </div>

                                <div class="mt-1 font-black">
                                    Verified Outcome
                                </div>
                            </div>

                            <span class="rounded-md bg-violet-400/10 px-2 py-1 text-xs font-bold text-violet-300">
                                WEBHOOK
                            </span>

                        </div>

                        <pre class="overflow-x-auto p-5 text-[11px] leading-5 text-slate-300"><code>{
  "event": "market.resolved",
  "market": {
    "id": 572,
    "status": "resolved",
    "resolved_at":
      "2026-12-31T23:59:59+00:00",
    "winning_answer_id": 1141
  }
}</code></pre>

                    </div>

                </div>


                <div class="mt-8 text-center">

                    <a
                        href="{{ url('/docs/getting-started') }}"
                        class="inline-flex rounded-xl bg-lime-400 px-6 py-4 text-sm font-black text-slate-950 transition hover:bg-lime-300"
                    >
                        Read API Documentation →
                    </a>

                </div>

            </div>

        </section>



        {{-- CATEGORIES --}}
        <section id="categories" class="bg-white py-16 sm:py-20">

            <div class="mx-auto max-w-7xl px-5 sm:px-8">

                <div class="text-center">

                    <p class="text-sm font-black uppercase tracking-[0.16em] text-emerald-600">
                        Content
                    </p>

                    <h2 class="mt-3 text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">
                        We understand the industry.
                        <span class="block text-emerald-600">
                            We understand the audience. We understand the content.
                        </span>
                    </h2>

                    <p class="mx-auto mt-4 max-w-3xl leading-7 text-slate-600">
                        Prediction market content across the topics your users follow,
                        researched, structured, localised and ready for delivery.
                    </p>

                </div>


                <div class="mt-10 grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-8">

                    @foreach ([
                        ['⚽', 'Sports'],
                        ['₿', 'Crypto'],
                        ['🎮', 'Esports'],
                        ['🏛️', 'Politics'],
                        ['🎬', 'Entertainment'],
                        ['📈', 'Finance'],
                        ['🧠', 'Technology'],
                        ['•••', 'More'],
                    ] as [$icon, $title])

                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-center transition hover:-translate-y-1 hover:bg-white hover:shadow-lg">

                            <div class="text-3xl">
                                {{ $icon }}
                            </div>

                            <div class="mt-3 text-sm font-black text-slate-950">
                                {{ $title }}
                            </div>

                        </div>

                    @endforeach

                </div>

            </div>

        </section>



        {{-- PRICING --}}
        <section id="pricing" class="bg-[#07111f] py-16 text-white sm:py-20">

            <div class="mx-auto max-w-7xl px-5 sm:px-8">

                <div class="text-center">

                    <p class="text-sm font-black uppercase tracking-[0.16em] text-lime-300">
                        Pricing
                    </p>

                    <h2 class="mt-3 text-3xl font-black tracking-tight sm:text-4xl">
                        Simple volume-based pricing.
                    </h2>

                    <p class="mx-auto mt-4 max-w-2xl text-slate-400">
                        Pay for the prediction markets delivered to your platform.
                        Your price per market decreases automatically as your monthly volume grows.
                    </p>

                </div>


                <div class="mt-12 grid gap-5 lg:grid-cols-3">

                    {{-- STARTER --}}
                    <div class="flex flex-col rounded-[1.5rem] border border-lime-400/30 bg-white/[0.04] p-6 sm:p-7">

                        <div class="text-sm font-black text-slate-300">
                            Starter
                        </div>

                        <div class="mt-3 text-sm font-bold text-lime-300">
                            Up to 599 markets / month
                        </div>

                        <div class="mt-5 flex items-end gap-2">

                            <span class="text-5xl font-black">
                                $10
                            </span>

                            <span class="pb-1.5 text-sm text-slate-400">
                                / delivered market
                            </span>

                        </div>

                        <p class="mt-5 min-h-16 text-sm leading-6 text-slate-400">
                            Start with any volume and pay only for the prediction markets
                            actually delivered to your platform.
                        </p>

                        <div class="mt-7 border-t border-white/10 pt-6">

                            <div class="mb-4 text-xs font-black uppercase tracking-[0.14em] text-slate-500">
                                Included
                            </div>

                            <div class="space-y-3">

                                @foreach ([
                                    'No monthly commitment',
                                    'Pay only for delivered markets',
                                    'Prediction market creation',
                                    'Multiple content categories',
                                    'Multilingual localisation',
                                    'API market delivery',
                                    'Continuous event monitoring',
                                    'AI-assisted outcome analysis',
                                    'Human verification',
                                    'Verified resolution callbacks',
                                ] as $feature)

                                    <div class="flex gap-3 text-sm text-slate-200">
                                        <span class="shrink-0 text-lime-300">✓</span>
                                        <span>{{ $feature }}</span>
                                    </div>

                                @endforeach

                            </div>

                        </div>

                        <a
                            href="#request-demo"
                            class="mt-8 inline-flex justify-center rounded-xl bg-lime-400 px-5 py-4 text-sm font-black text-slate-950 transition hover:bg-lime-300"
                        >
                            Request Demo
                        </a>

                    </div>


                    {{-- GROWTH --}}
                    <div class="relative flex flex-col rounded-[1.5rem] border border-sky-400/40 bg-white/[0.06] p-6 shadow-2xl shadow-blue-950/20 sm:p-7">

                        <div class="absolute -top-3 left-6 rounded-full bg-sky-500 px-3 py-1 text-[10px] font-black uppercase tracking-wider text-white">
                            Best Value
                        </div>

                        <div class="text-sm font-black text-slate-300">
                            Growth
                        </div>

                        <div class="mt-3 text-sm font-bold text-sky-300">
                            600–1,500 markets / month
                        </div>

                        <div class="mt-5 flex items-end gap-2">

                            <span class="text-5xl font-black">
                                $6.50
                            </span>

                            <span class="pb-1.5 text-sm text-slate-400">
                                / delivered market
                            </span>

                        </div>

                        <p class="mt-5 min-h-16 text-sm leading-6 text-slate-400">
                            Built for growing platforms that need a continuous flow of
                            prediction market content at a lower per-market cost.
                        </p>

                        <div class="mt-7 border-t border-white/10 pt-6">

                            <div class="mb-4 text-xs font-black uppercase tracking-[0.14em] text-slate-500">
                                Included
                            </div>

                            <div class="space-y-3">

                                @foreach ([
                                    '$6.50 per delivered market',
                                    'Volume pricing from 600 markets',
                                    'Prediction market creation',
                                    'Multiple content categories',
                                    'Multiple languages and regions',
                                    'API market delivery',
                                    'Continuous event monitoring',
                                    'AI-assisted outcome analysis',
                                    'Human verification',
                                    'Verified resolution callbacks',
                                ] as $feature)

                                    <div class="flex gap-3 text-sm text-slate-200">
                                        <span class="shrink-0 text-sky-300">✓</span>
                                        <span>{{ $feature }}</span>
                                    </div>

                                @endforeach

                            </div>

                        </div>

                        <a
                            href="#request-demo"
                            class="mt-8 inline-flex justify-center rounded-xl bg-sky-500 px-5 py-4 text-sm font-black text-white transition hover:bg-sky-400"
                        >
                            Request Demo
                        </a>

                    </div>


                    {{-- SCALE --}}
                    <div class="flex flex-col rounded-[1.5rem] border border-violet-400/30 bg-white/[0.04] p-6 sm:p-7">

                        <div class="text-sm font-black text-slate-300">
                            Scale
                        </div>

                        <div class="mt-3 text-sm font-bold text-violet-300">
                            1,501+ markets / month
                        </div>

                        <div class="mt-5 flex items-end gap-2">

                            <span class="text-5xl font-black">
                                $5
                            </span>

                            <span class="pb-1.5 text-sm text-slate-400">
                                / delivered market
                            </span>

                        </div>

                        <p class="mt-5 min-h-16 text-sm leading-6 text-slate-400">
                            Our lowest per-market rate for high-volume operators running
                            prediction market content at scale.
                        </p>

                        <div class="mt-7 border-t border-white/10 pt-6">

                            <div class="mb-4 text-xs font-black uppercase tracking-[0.14em] text-slate-500">
                                Included
                            </div>

                            <div class="space-y-3">

                                @foreach ([
                                    '$5 per delivered market',
                                    'No market volume limit',
                                    'Prediction market creation',
                                    'Multiple content categories',
                                    'Multiple languages and regions',
                                    'API market delivery',
                                    'Continuous event monitoring',
                                    'AI-assisted outcome analysis',
                                    'Human verification',
                                    'Verified resolution callbacks',
                                ] as $feature)

                                    <div class="flex gap-3 text-sm text-slate-200">
                                        <span class="shrink-0 text-violet-300">✓</span>
                                        <span>{{ $feature }}</span>
                                    </div>

                                @endforeach

                            </div>

                        </div>

                        <a
                            href="#request-demo"
                            class="mt-8 inline-flex justify-center rounded-xl bg-violet-600 px-5 py-4 text-sm font-black text-white transition hover:bg-violet-500"
                        >
                            Request Demo
                        </a>

                    </div>

                </div>


                <div class="mx-auto mt-10 max-w-4xl rounded-2xl border border-white/10 bg-white/[0.03] p-6 sm:p-7">

                    <div class="grid gap-6 sm:grid-cols-[auto_1fr] sm:items-start">

                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-lime-400/10 text-xl font-black text-lime-300">
                            $
                        </div>

                        <div>

                            <h3 class="font-black text-white">
                                Your rate follows your monthly volume.
                            </h3>

                            <p class="mt-2 text-sm leading-6 text-slate-400">
                                Pricing is based on the number of prediction markets delivered
                                to your platform during the month. As your volume grows, your
                                per-market rate moves to the corresponding pricing tier.
                            </p>

                        </div>

                    </div>

                </div>

            </div>

        </section>



        {{-- REQUEST DEMO --}}
        <livewire:public.request-demo />



        {{-- FOOTER --}}
        <footer id="company" class="border-t border-slate-200 bg-white py-8">

            <div class="mx-auto flex max-w-7xl flex-col gap-4 px-5 text-sm text-slate-500 sm:px-8 md:flex-row md:items-center md:justify-between">

                <div class="font-black text-slate-950">
                    wrangle<span class="text-lime-500">.win</span>
                </div>

                <div>
                    Content built for prediction markets.
                </div>

                <div>
                    © {{ date('Y') }} wrangle.win
                </div>

            </div>

        </footer>

    </div>

@endsection
