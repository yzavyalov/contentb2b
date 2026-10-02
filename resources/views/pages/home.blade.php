@extends('layouts.public')

@section('title', 'wrangle.win — Prediction Market Content Infrastructure')

@section('description', 'Launch prediction markets without building an in-house content and resolution operation. wrangle.win creates, localizes, monitors and resolves prediction markets delivered through API.')

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
                            Prediction Market Content Infrastructure
                        </div>

                        <h1 class="mt-6 max-w-2xl text-5xl font-black leading-[0.98] tracking-tight sm:text-6xl xl:text-7xl">
                            Launch Prediction Markets.

                            <span class="mt-2 block bg-gradient-to-r from-lime-300 via-emerald-300 to-cyan-300 bg-clip-text text-transparent">
                            We Handle the Rest.
                        </span>
                        </h1>

                        <p class="mt-7 max-w-xl text-lg leading-8 text-slate-300">
                            Add prediction markets to your platform without building an in-house
                            content and resolution operation.
                            We create structured markets, localize them, deliver them through API,
                            monitor real-world events and send verified resolution callbacks when
                            outcomes are known.
                        </p>

                        <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:flex-wrap">

                            <a
                                href="{{ url('/docs/getting-started') }}"
                                class="inline-flex items-center justify-center rounded-xl bg-lime-400 px-6 py-4 text-sm font-black text-slate-950 transition hover:bg-lime-300"
                            >
                                Get API Access
                            </a>

                            <a
                                href="https://example.wrangle.win"
                                target="_blank"
                                rel="noopener"
                                class="inline-flex items-center justify-center gap-2 rounded-xl border border-white/20 px-6 py-4 text-sm font-black text-white transition hover:bg-white/5"
                            >
                                View Live Demo

                                <svg
                                    viewBox="0 0 24 24"
                                    class="h-4 w-4"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path d="M14 5h5v5M10 14L19 5M19 13v6H5V5h6"/>
                                </svg>
                            </a>

                            <a
                                href="#demo"
                                class="inline-flex items-center justify-center rounded-xl border border-white/20 px-6 py-4 text-sm font-black text-white transition hover:bg-white/5"
                            >
                                Contact Sales
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
                                    Market Delivery
                                </div>
                            </div>

                            <div>
                                <div class="text-2xl font-black text-lime-300">Human</div>
                                <div class="mt-1 text-xs font-semibold text-slate-400">
                                    Verified Results
                                </div>
                            </div>

                            <div>
                                <div class="text-2xl font-black text-lime-300">0%</div>
                                <div class="mt-1 text-xs font-semibold text-slate-400">
                                    Revenue Share
                                </div>
                            </div>

                        </div>

                    </div>


                    {{-- Live markets preview --}}
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
                                    ['₿', 'CRYPTO', 'Will Bitcoin trade above $150,000 on December 31?', 'Yes', 'No'],
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
                                href="https://example.wrangle.win"
                                target="_blank"
                                rel="noopener"
                                class="flex items-center justify-center gap-2 border-t border-white/10 px-5 py-5 text-sm font-black text-cyan-300 transition hover:bg-white/[0.03]"
                            >
                                Explore markets on our live demo
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

                <div class="mx-auto max-w-3xl text-center">

                    <p class="text-sm font-black uppercase tracking-[0.16em] text-emerald-600">
                        Infrastructure, not another betting platform
                    </p>

                    <h2 class="mt-3 text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">
                        You run the platform.
                        <span class="text-emerald-600">We run the content operation.</span>
                    </h2>

                    <p class="mt-5 text-lg leading-8 text-slate-600">
                        Your users, balances, positions, commercial model and settlement economics
                        remain inside your platform. wrangle.win provides the prediction market
                        content layer behind the experience.
                    </p>

                </div>


                <div class="mt-12 grid gap-5 md:grid-cols-2 lg:grid-cols-4">

                    @foreach ([
                        [
                            '✦',
                            'Market Creation',
                            'Structured prediction markets with clear outcomes and defined resolution conditions.'
                        ],
                        [
                            '🌍',
                            'Multilingual Content',
                            'Deliver localized prediction market content across languages and regions.'
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
                        Workflow
                    </p>

                    <h2 class="mt-3 text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">
                        From market idea to verified result
                    </h2>

                    <p class="mx-auto mt-4 max-w-2xl leading-7 text-slate-600">
                        One integration gives your platform a continuous prediction market
                        content and resolution pipeline.
                    </p>

                </div>


                <div class="mt-12 grid gap-7 sm:grid-cols-2 lg:grid-cols-5">

                    @foreach ([
                        ['01', 'We Create', 'Markets are researched and prepared with structured outcomes and resolution conditions.'],
                        ['02', 'API Delivery', 'Receive market content in the categories and languages required by your platform.'],
                        ['03', 'You Publish', 'Choose what to publish and manage the customer experience entirely on your side.'],
                        ['04', 'We Monitor', 'The underlying event is continuously monitored using defined result sources and conditions.'],
                        ['05', 'We Resolve', 'Verified outcomes are delivered back to your platform through resolution callbacks.'],
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



        {{-- API --}}
        <section id="api" class="bg-[#07111f] py-16 text-white sm:py-20">

            <div class="mx-auto max-w-7xl px-5 sm:px-8">

                <div class="grid gap-12 lg:grid-cols-[0.75fr_1.25fr] lg:items-center">

                    <div>

                        <p class="text-sm font-black uppercase tracking-[0.16em] text-lime-300">
                            API First
                        </p>

                        <h2 class="mt-3 text-3xl font-black tracking-tight sm:text-4xl">
                            Content in.
                            <span class="block text-lime-300">
                            Verified results back.
                        </span>
                        </h2>

                        <p class="mt-5 max-w-lg leading-7 text-slate-300">
                            Receive structured prediction markets through API and integrate
                            them into your existing product. When an event is resolved,
                            your platform receives the verified outcome.
                        </p>


                        <div class="mt-8 space-y-4">

                            <div class="flex gap-3">

                                <span class="mt-0.5 text-lime-300">✓</span>

                                <div>
                                    <div class="font-bold">Structured market data</div>
                                    <div class="mt-1 text-sm text-slate-400">
                                        Questions, outcomes, dates, sources and resolution rules.
                                    </div>
                                </div>

                            </div>

                            <div class="flex gap-3">

                                <span class="mt-0.5 text-lime-300">✓</span>

                                <div>
                                    <div class="font-bold">Multilingual delivery</div>
                                    <div class="mt-1 text-sm text-slate-400">
                                        Integrate localized content without maintaining your own content operation.
                                    </div>
                                </div>

                            </div>

                            <div class="flex gap-3">

                                <span class="mt-0.5 text-lime-300">✓</span>

                                <div>
                                    <div class="font-bold">Resolution callbacks</div>
                                    <div class="mt-1 text-sm text-slate-400">
                                        Receive the verified winning outcome after resolution.
                                    </div>
                                </div>

                            </div>

                        </div>


                        <a
                            href="{{ url('/docs/getting-started') }}"
                            class="mt-8 inline-flex rounded-xl bg-lime-400 px-6 py-4 text-sm font-black text-slate-950 transition hover:bg-lime-300"
                        >
                            Read API Documentation →
                        </a>

                    </div>


                    {{-- API examples --}}
                    <div class="grid gap-5 xl:grid-cols-2">

                        {{-- Market --}}
                        <div class="overflow-hidden rounded-[1.5rem] border border-white/10 bg-[#0b1727]">

                            <div class="flex items-center justify-between border-b border-white/10 px-5 py-4">

                                <div>
                                    <div class="text-xs font-bold uppercase tracking-wider text-slate-500">
                                        Market Delivery
                                    </div>

                                    <div class="mt-1 font-black">
                                        Example Market
                                    </div>
                                </div>

                                <span class="rounded-md bg-emerald-400/10 px-2 py-1 text-xs font-bold text-emerald-300">
                                JSON
                            </span>

                            </div>

                            <pre class="overflow-x-auto p-5 text-[11px] leading-5 text-slate-300"><code>{
  "id": 18452,
  "category": "crypto",
  "language": "en",
  "question":
    "Will Bitcoin trade above
     $150,000 on December 31?",
  "outcomes": [
    "yes",
    "no"
  ],
  "end_at":
    "2026-12-31T23:59:59Z",
  "result_sources": [
    "https://source-one.example/",
    "https://source-two.example/"
  ],
  "resolution_rule":
    "Bitcoin price must be above
     $150,000 at the specified
     market end time.",
  "status": "published"
}</code></pre>

                        </div>


                        {{-- Callback --}}
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
    "source_locale": "en",
    "resolved_at":
      "2026-10-15T18:15:00+00:00",
    "winning_answer_id": 1141,

    "answers": [
      {
        "id": 1141,
        "sort_order": 1,
        "is_winner": true,
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
        "is_winner": false
      }
    ],

    "winning_answer": {
      "id": 1141,
      "translations": [
        {
          "locale": "en",
          "title": "Yes"
        }
      ]
    }
  }
}</code></pre>

                        </div>

                    </div>

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
                        Markets across the topics your users follow
                    </h2>

                    <p class="mx-auto mt-4 max-w-2xl leading-7 text-slate-600">
                        Build a broad prediction market offering without maintaining
                        separate research and content teams for every category.
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



        {{-- NO REVENUE SHARE --}}
        <section class="border-y border-slate-200 bg-slate-50 py-16">

            <div class="mx-auto max-w-7xl px-5 sm:px-8">

                <div class="grid gap-10 lg:grid-cols-[1fr_0.8fr] lg:items-center">

                    <div>

                        <p class="text-sm font-black uppercase tracking-[0.16em] text-emerald-600">
                            Your economics stay yours
                        </p>

                        <h2 class="mt-3 text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">
                            0% Revenue Share
                        </h2>

                        <p class="mt-5 max-w-2xl text-lg leading-8 text-slate-600">
                            wrangle.win charges for prediction market content and infrastructure.
                            We do not take a percentage of your revenue, customer stakes,
                            winnings or payouts.
                        </p>

                        <p class="mt-4 max-w-2xl leading-7 text-slate-600">
                            You control how users participate, how positions are calculated,
                            how balances are managed and how customers are settled.
                        </p>

                    </div>


                    <div class="grid grid-cols-2 gap-4">

                        <div class="rounded-2xl bg-white p-6 shadow-sm">
                            <div class="text-3xl font-black text-emerald-600">0%</div>
                            <div class="mt-2 font-black text-slate-950">Revenue Share</div>
                        </div>

                        <div class="rounded-2xl bg-white p-6 shadow-sm">
                            <div class="text-3xl font-black text-emerald-600">0%</div>
                            <div class="mt-2 font-black text-slate-950">Stake Percentage</div>
                        </div>

                        <div class="rounded-2xl bg-white p-6 shadow-sm">
                            <div class="text-xl font-black text-emerald-600">Your</div>
                            <div class="mt-2 font-black text-slate-950">Users & Balances</div>
                        </div>

                        <div class="rounded-2xl bg-white p-6 shadow-sm">
                            <div class="text-xl font-black text-emerald-600">Your</div>
                            <div class="mt-2 font-black text-slate-950">Commercial Model</div>
                        </div>

                    </div>

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
                        Start small. Scale when you're ready.
                    </h2>

                    <p class="mx-auto mt-4 max-w-2xl text-slate-400">
                        Simple infrastructure pricing with no revenue share.
                        Start at $10 per delivered market or move to a monthly package as your volume grows.
                    </p>

                </div>


                <div class="mt-10 grid gap-5 lg:grid-cols-3">

                    {{-- STARTER --}}
                    <div class="flex flex-col rounded-[1.5rem] border border-lime-400/30 bg-white/[0.04] p-6 sm:p-7">

                        <div class="text-sm font-black text-slate-300">
                            Starter
                        </div>

                        <div class="mt-4 flex items-end gap-2">

                        <span class="text-4xl font-black">
                            $10
                        </span>

                            <span class="pb-1 text-sm text-slate-400">
                            / market
                        </span>

                        </div>

                        <p class="mt-4 min-h-12 text-sm leading-6 text-slate-400">
                            Pay only for delivered markets. Start without a monthly commitment.
                        </p>

                        <div class="mt-6 space-y-3">

                            @foreach ([
                                '$0 monthly commitment',
                                '$10 per delivered market',
                                'Prediction market creation',
                                'Multilingual localization',
                                'Event monitoring',
                                'AI-assisted outcome analysis',
                                'Human verification',
                                'Verified resolution callbacks',
                                'API delivery',
                            ] as $feature)

                                <div class="flex gap-3 text-sm text-slate-200">
                                    <span class="text-lime-300">✓</span>
                                    <span>{{ $feature }}</span>
                                </div>

                            @endforeach

                        </div>

                        <a
                            href="{{ url('/pricing') }}"
                            class="mt-8 inline-flex justify-center rounded-xl bg-lime-400 px-5 py-4 text-sm font-black text-slate-950 transition hover:bg-lime-300"
                        >
                            Start with Starter
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

                        <div class="mt-4">

                            <div class="flex items-end gap-2">

                            <span class="text-4xl font-black">
                                $4,000
                            </span>

                                <span class="pb-1 text-sm text-slate-400">
                                / month
                            </span>

                            </div>

                            <div class="mt-2 text-sm font-bold text-sky-300">
                                600 markets included
                            </div>

                        </div>

                        <p class="mt-4 min-h-12 text-sm leading-6 text-slate-400">
                            A continuous prediction market pipeline at a lower effective cost per included market.
                        </p>

                        <div class="mt-6 space-y-3">

                            @foreach ([
                                '600 markets included every month',
                                'Approx. $6.67 per included market at full usage',
                                '$10 for each additional market',
                                'Multiple content categories',
                                'Multiple languages',
                                'Continuous event monitoring',
                                'AI-assisted outcome analysis',
                                'Human verification',
                                'Verified resolution callbacks',
                                'API delivery',
                            ] as $feature)

                                <div class="flex gap-3 text-sm text-slate-200">
                                    <span class="text-lime-300">✓</span>
                                    <span>{{ $feature }}</span>
                                </div>

                            @endforeach

                        </div>

                        <a
                            href="{{ url('/pricing') }}"
                            class="mt-8 inline-flex justify-center rounded-xl bg-sky-500 px-5 py-4 text-sm font-black text-white transition hover:bg-sky-400"
                        >
                            Choose Growth
                        </a>

                    </div>


                    {{-- SCALE --}}
                    <div class="flex flex-col rounded-[1.5rem] border border-violet-400/30 bg-white/[0.04] p-6 sm:p-7">

                        <div class="text-sm font-black text-slate-300">
                            Scale
                        </div>

                        <div class="mt-4">

                            <div class="flex items-end gap-2">

                            <span class="text-4xl font-black">
                                $8,000
                            </span>

                                <span class="pb-1 text-sm text-slate-400">
                                / month
                            </span>

                            </div>

                            <div class="mt-2 text-sm font-bold text-violet-300">
                                Unlimited markets
                            </div>

                        </div>

                        <p class="mt-4 min-h-12 text-sm leading-6 text-slate-400">
                            Predictable fixed monthly pricing for high-volume operators.
                        </p>

                        <div class="mt-6 space-y-3">

                            @foreach ([
                                'Unlimited market volume',
                                'No additional per-market charges',
                                'Multiple content categories',
                                'Multiple languages',
                                'Continuous event monitoring',
                                'AI-assisted outcome analysis',
                                'Human verification',
                                'Verified resolution callbacks',
                                'API delivery',
                            ] as $feature)

                                <div class="flex gap-3 text-sm text-slate-200">
                                    <span class="text-lime-300">✓</span>
                                    <span>{{ $feature }}</span>
                                </div>

                            @endforeach

                        </div>

                        <a
                            href="{{ url('/pricing') }}"
                            class="mt-8 inline-flex justify-center rounded-xl bg-violet-600 px-5 py-4 text-sm font-black text-white transition hover:bg-violet-500"
                        >
                            Choose Scale
                        </a>

                    </div>

                </div>


                <div class="mt-8 text-center">

                    <a
                        href="{{ url('/pricing') }}"
                        class="text-sm font-bold text-slate-400 transition hover:text-white"
                    >
                        Compare plans and pricing details →
                    </a>

                </div>

            </div>

        </section>



        {{-- FINAL CTA --}}
        <section id="demo" class="bg-white py-14 sm:py-16">

            <div class="mx-auto max-w-7xl px-5 sm:px-8">

                <div class="overflow-hidden rounded-[1.5rem] bg-gradient-to-r from-cyan-600 via-blue-700 to-violet-700 p-7 text-white shadow-xl sm:p-10">

                    <div class="flex flex-col gap-7 lg:flex-row lg:items-center lg:justify-between">

                        <div class="max-w-2xl">

                            <h2 class="text-3xl font-black tracking-tight">
                                Add prediction markets without building the entire operation behind them.
                            </h2>

                            <p class="mt-3 leading-7 text-white/80">
                                One API integration for market creation, localization,
                                monitoring and verified resolution delivery.
                            </p>

                        </div>


                        <div class="flex flex-col gap-3 sm:flex-row">

                            <a
                                href="mailto:sales@wrangle.win"
                                class="rounded-xl bg-lime-400 px-6 py-4 text-center text-sm font-black text-slate-950 transition hover:bg-lime-300"
                            >
                                Get API Access
                            </a>

                            <a
                                href="https://example.wrangle.win"
                                target="_blank"
                                rel="noopener"
                                class="rounded-xl border border-white/25 px-6 py-4 text-center text-sm font-black text-white transition hover:bg-white/10"
                            >
                                View Live Demo ↗
                            </a>

                        </div>

                    </div>

                </div>

            </div>

        </section>



        {{-- FOOTER --}}
        <footer id="company" class="border-t border-slate-200 bg-white py-8">

            <div class="mx-auto flex max-w-7xl flex-col gap-4 px-5 text-sm text-slate-500 sm:px-8 md:flex-row md:items-center md:justify-between">

                <div class="font-black text-slate-950">
                    wrangle<span class="text-lime-500">.win</span>
                </div>

                <div>
                    Prediction Market Content Infrastructure
                </div>

                <div>
                    © {{ date('Y') }} wrangle.win
                </div>

            </div>

        </footer>

    </div>

@endsection
