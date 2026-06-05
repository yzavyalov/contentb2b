<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>wrangle.win — Premium Betting Content & Real-time Results</title>
    <meta name="description" content="Premium betting content, multilingual markets and real-time callbacks for online casinos and betting platforms.">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://wrangle.win">

    <meta property="og:type" content="website">
    <meta property="og:url" content="https://wrangle.win">
    <meta property="og:title" content="wrangle.win — Premium Betting Content & Real-time Results">
    <meta property="og:description" content="Ready-to-use betting markets, 30+ languages and instant webhook callbacks for iGaming platforms.">
    <meta property="og:image" content="{{ asset('images/og/wrangle-win-og.jpg') }}">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="wrangle.win — Premium Betting Content & Real-time Results">
    <meta name="twitter:description" content="Premium betting content and real-time callbacks for iGaming platforms.">
    <meta name="twitter:image" content="{{ asset('images/og/wrangle-win-og.jpg') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @verbatim
        <script type="application/ld+json">
            {
                "@context": "https://schema.org",
        "@type": "Organization",
        "name": "wrangle.win",
        "url": "https://wrangle.win",
        "description": "Premium betting content and real-time result callbacks for online casinos and betting platforms."
    }
        </script>
    @endverbatim
</head>
<body class="bg-[#f8f9ff] text-[#0f172a] antialiased">
<main class="min-h-screen overflow-hidden">
    <section class="relative overflow-hidden bg-white">
        <div class="absolute -right-32 top-0 h-[680px] w-[680px] rotate-12 bg-lime-300"></div>
        <div class="absolute right-[26%] top-0 h-full w-[260px] -skew-x-12 bg-violet-50"></div>
        <div class="absolute -left-24 bottom-24 h-52 w-52 rotate-45 bg-violet-600"></div>

        <div class="relative mx-auto max-w-7xl px-5 py-6 sm:px-8">
            <header class="flex items-center justify-between gap-6">
                <a href="{{ url('/') }}" class="text-3xl font-black tracking-tight text-slate-950" aria-label="wrangle.win home">
                    wrangle<span class="text-violet-600">.win</span>
                </a>

                <nav class="hidden items-center gap-8 text-sm font-semibold text-slate-800 lg:flex" aria-label="Main navigation">
                    <a class="hover:text-violet-600" href="#products">Products</a>
                    <a class="hover:text-violet-600" href="#categories">Categories</a>
                    <a class="hover:text-violet-600" href="#languages">Languages</a>
                    <a class="hover:text-violet-600" href="#callbacks">Callbacks</a>
                    <a class="hover:text-violet-600" href="#api">API</a>
                    <a class="hover:text-violet-600" href="#about">About Us</a>
                </nav>

                <div class="hidden items-center gap-4 md:flex">
                    <a href="#demo" class="rounded-xl bg-violet-600 px-7 py-4 text-sm font-black text-white shadow-xl shadow-violet-500/20 transition hover:bg-violet-500">
                        Request a Demo
                    </a>
                    <a href="/login" class="rounded-xl border border-slate-200 bg-white px-6 py-4 text-sm font-black text-slate-950 shadow-sm transition hover:border-violet-200 hover:text-violet-600">
                        Log In
                    </a>
                </div>

                <button class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm lg:hidden" type="button" aria-label="Open menu">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </header>

            <div class="grid items-center gap-12 pb-24 pt-20 lg:grid-cols-[0.9fr_1.1fr] lg:pt-28">
                <div>
                    <h1 class="max-w-2xl text-5xl font-black leading-[1.04] tracking-tight text-slate-950 sm:text-6xl lg:text-7xl">
                        Content for bets. Results in <span class="text-violet-600">real time.</span>
                    </h1>

                    <p class="mt-8 max-w-xl text-lg leading-8 text-slate-700">
                        We deliver premium betting content on any topic and category for online casinos and betting companies worldwide.
                    </p>

                    <div class="mt-7 space-y-4">
                        @foreach ([
                            'Thousands of ready-to-use betting markets',
                            '30+ languages and localizations',
                            'High accuracy and 99.9% uptime',
                            'Real-time callbacks with results',
                            'Easy integration via REST API',
                        ] as $item)
                            <div class="flex items-center gap-3 text-base font-semibold text-slate-800">
                                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-lime-300 text-sm font-black text-slate-950">✓</span>
                                {{ $item }}
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-9 flex flex-col gap-4 sm:flex-row">
                        <a href="#demo" class="inline-flex items-center justify-center gap-3 rounded-xl bg-violet-600 px-7 py-4 text-sm font-black text-white shadow-xl shadow-violet-500/20 transition hover:bg-violet-500">
                            <span class="text-lg">→</span>
                            Request a Demo
                        </a>
                        <a href="/docs" class="inline-flex items-center justify-center gap-3 rounded-xl border border-slate-200 bg-white px-7 py-4 text-sm font-black text-slate-950 shadow-sm transition hover:border-violet-200 hover:text-violet-600">
                            <span>▣</span>
                            API Documentation
                        </a>
                    </div>
                </div>

                <div class="relative">
                    <div class="absolute -bottom-10 -right-6 h-56 w-80 rounded-[2rem] bg-lime-300 blur-2xl"></div>
                    <div class="relative rotate-2 rounded-[2rem] bg-[#101722] p-6 shadow-[0_40px_90px_rgba(15,23,42,0.35)] ring-1 ring-slate-900/10 lg:p-8">
                        <div class="mb-6 flex items-center justify-between">
                            <div>
                                <p class="text-xl font-black text-white">wrangle.win</p>
                                <h2 class="mt-7 text-2xl font-black text-white">Popular Betting Markets</h2>
                            </div>
                            <span class="rounded-full bg-violet-600/25 px-3 py-1 text-xs font-black text-violet-200">Live</span>
                        </div>

                        <div class="space-y-4">
                            @foreach ([
                                ['icon' => '⚽', 'tag' => 'Sports', 'question' => 'Will Messi score in the next match?', 'yes' => '1.85', 'no' => '1.95'],
                                ['icon' => '🎬', 'tag' => 'Movies & TV', 'question' => 'Will Oppenheimer win Best Picture at the Oscars?', 'yes' => '1.70', 'no' => '2.10'],
                                ['icon' => '₿', 'tag' => 'Crypto', 'question' => 'Will Bitcoin exceed $100,000 by the end of the year?', 'yes' => '1.75', 'no' => '2.05'],
                                ['icon' => '📈', 'tag' => 'Business', 'question' => 'Will Apple reach $200 per share this year?', 'yes' => '1.60', 'no' => '2.20'],
                            ] as $market)
                                <article class="grid grid-cols-[54px_1fr_62px_62px] items-center gap-4 rounded-2xl border border-white/10 bg-white/[0.04] p-4 text-white sm:grid-cols-[64px_1fr_76px_76px]">
                                    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-violet-600 text-3xl shadow-lg shadow-violet-950/40">{{ $market['icon'] }}</div>
                                    <div>
                                        <span class="inline-flex rounded-lg bg-violet-600/40 px-2 py-1 text-xs font-bold text-violet-100">{{ $market['tag'] }}</span>
                                        <h3 class="mt-1 text-sm font-black leading-snug sm:text-base">{{ $market['question'] }}</h3>
                                    </div>
                                    <div class="border-l border-white/10 text-center">
                                        <p class="text-xs text-slate-300">Yes</p>
                                        <p class="font-black text-emerald-300">{{ $market['yes'] }}</p>
                                    </div>
                                    <div class="border-l border-white/10 text-center">
                                        <p class="text-xs text-slate-300">No</p>
                                        <p class="font-black text-rose-400">{{ $market['no'] }}</p>
                                    </div>
                                </article>
                            @endforeach
                        </div>

                        <a href="#categories" class="mt-6 flex items-center justify-between rounded-2xl bg-violet-600 px-6 py-5 font-black text-white transition hover:bg-violet-500">
                            View All Markets
                            <span class="text-2xl">→</span>
                        </a>
                    </div>
                </div>
            </div>

            <div class="relative -mb-14 rounded-[1.7rem] bg-white p-5 shadow-[0_30px_70px_rgba(15,23,42,0.12)] ring-1 ring-slate-200 sm:p-8">
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-5">
                    @foreach ([
                        ['icon' => '▤', 'value' => '100K+', 'label' => 'Active Markets'],
                        ['icon' => '◎', 'value' => '30+', 'label' => 'Languages'],
                        ['icon' => '⚡', 'value' => '<1 sec', 'label' => 'Callback Latency'],
                        ['icon' => '◈', 'value' => '99.9%', 'label' => 'Uptime'],
                        ['icon' => '☏', 'value' => '24/7', 'label' => 'Support'],
                    ] as $stat)
                        <div class="flex items-center gap-5 border-slate-200 lg:border-r last:border-r-0">
                            <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full {{ $loop->even ? 'bg-lime-300 text-slate-950' : 'bg-violet-600 text-white' }} text-3xl font-black">
                                {{ $stat['icon'] }}
                            </div>
                            <div>
                                <p class="text-3xl font-black text-slate-950">{{ $stat['value'] }}</p>
                                <p class="text-sm font-semibold text-slate-600">{{ $stat['label'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <section id="products" class="px-5 pb-16 pt-28 sm:px-8">
        <div class="mx-auto max-w-7xl">
            <h2 class="text-center text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">
                Why Choose wrangle.win?
            </h2>

            <div class="mt-10 grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['icon' => '▦', 'title' => 'Unique Content', 'text' => 'Wide selection of original betting markets across any topic and category. Created by our team of experts.'],
                    ['icon' => '✣', 'title' => 'Seamless Integration', 'text' => 'Well-documented REST API and SDKs in multiple languages. Get up and running in minutes.'],
                    ['icon' => '⌘', 'title' => 'Real-time Callbacks', 'text' => 'Instant results delivered via Webhook JSON. Reliable, secure and easy to configure.'],
                    ['icon' => '↗', 'title' => 'Higher Revenue', 'text' => 'Increase engagement and retention with premium markets and real-time result updates.'],
                ] as $feature)
                    <article class="rounded-[1.7rem] bg-white p-7 shadow-[0_20px_50px_rgba(15,23,42,0.06)] ring-1 ring-slate-200 transition hover:-translate-y-1 hover:shadow-[0_30px_70px_rgba(15,23,42,0.12)]">
                        <div class="mb-8 flex h-16 w-16 items-center justify-center rounded-2xl {{ $loop->even ? 'bg-lime-300 text-slate-950' : 'bg-violet-600 text-white' }} text-4xl font-black">
                            {{ $feature['icon'] }}
                        </div>
                        <h3 class="text-xl font-black text-slate-950">{{ $feature['title'] }}</h3>
                        <p class="mt-4 text-sm leading-7 text-slate-600">{{ $feature['text'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="px-5 pb-0 sm:px-8">
        <div class="mx-auto max-w-7xl overflow-hidden rounded-[1.7rem] bg-gradient-to-r from-violet-700 to-violet-950 px-8 py-9 text-white shadow-2xl shadow-violet-900/20">
            <p class="mb-7 text-center text-sm font-bold text-violet-100">Trusted by leading companies</p>
            <div class="grid grid-cols-2 items-center gap-8 text-center text-3xl font-black text-white/85 sm:grid-cols-4 lg:grid-cols-7">
                <span>bet365</span>
                <span>1win</span>
                <span>PINNACLE</span>
                <span>BC.GAME</span>
                <span>888</span>
                <span>betsson</span>
                <span>RABONA</span>
            </div>
        </div>
    </section>

    <section id="callbacks" class="px-5 pb-16 sm:px-8">
        <div class="mx-auto grid max-w-7xl overflow-hidden rounded-b-[1.7rem] bg-white shadow-[0_24px_70px_rgba(15,23,42,0.08)] ring-1 ring-slate-200 lg:grid-cols-[0.9fr_1.4fr]">
            <div class="bg-violet-700 p-8 text-white sm:p-10">
                <p class="text-sm font-black uppercase tracking-[0.15em] text-violet-200">Callbacks</p>
                <h2 class="mt-4 text-4xl font-black leading-tight tracking-tight">Get results in real time</h2>
                <p class="mt-5 text-violet-100">Our system instantly delivers results for all markets via your webhook.</p>

                <div class="mt-8 grid gap-3 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2">
                    @foreach (['Reliable delivery', 'Easy setup', 'JSON format', 'Result history', 'Secure & private', 'Fast retries'] as $item)
                        <p class="flex items-center gap-3 text-sm font-semibold">
                            <span class="flex h-5 w-5 items-center justify-center rounded-full bg-lime-300 text-xs font-black text-slate-950">✓</span>
                            {{ $item }}
                        </p>
                    @endforeach
                </div>

                <a href="/docs/callbacks" class="mt-8 inline-flex items-center gap-3 rounded-xl bg-white px-6 py-4 text-sm font-black text-violet-700 transition hover:bg-violet-50">
                    Learn more about callbacks
                    <span>→</span>
                </a>
            </div>

            <div class="grid gap-5 p-6 sm:p-10 md:grid-cols-3">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-6 text-center">
                    <h3 class="font-black text-slate-950">Our System</h3>
                    <div class="mt-8 space-y-3">
                        <div class="mx-auto h-8 w-24 rounded-lg bg-violet-600"></div>
                        <div class="mx-auto h-8 w-24 rounded-lg bg-violet-500"></div>
                        <div class="mx-auto h-8 w-24 rounded-lg bg-violet-400"></div>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-6">
                    <h3 class="text-center font-black text-slate-950">Webhook JSON</h3>
                    <pre class="mt-5 overflow-hidden rounded-xl bg-slate-950 p-4 text-xs leading-6 text-slate-100">{
  "event": "market_settled",
  "market_id": "12345",
  "outcome": "1",
  "result": "win",
  "settled_at": "2026-05-06T12:30:00Z"
}</pre>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-6 text-center">
                    <h3 class="font-black text-slate-950">Your Platform</h3>
                    <div class="mx-auto mt-8 h-20 w-24 rounded-xl border-8 border-violet-600"></div>
                    <p class="mt-5 text-sm text-slate-600">Result displayed to the user</p>
                </div>
            </div>
        </div>
    </section>

    <section id="categories" class="px-5 pb-16 sm:px-8">
        <div class="mx-auto max-w-7xl">
            <h2 class="text-center text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">Popular Categories</h2>

            <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-6">
                @foreach ([
                    ['⚽', 'Sports', '25,000+ markets'],
                    ['🎮', 'Esports', '15,000+ markets'],
                    ['₿', 'Crypto', '8,000+ markets'],
                    ['🎬', 'Movies & TV', '6,000+ markets'],
                    ['🏛️', 'Politics', '4,000+ markets'],
                    ['•••', 'More', 'All categories'],
                ] as $category)
                    <article class="flex items-center gap-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 transition hover:-translate-y-1 hover:shadow-xl">
                        <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-slate-100 text-3xl font-black">{{ $category[0] }}</div>
                        <div>
                            <h3 class="font-black text-slate-950">{{ $category[1] }}</h3>
                            <p class="text-xs font-semibold text-slate-500">{{ $category[2] }}</p>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section id="demo" class="px-5 pb-20 sm:px-8">
        <div class="mx-auto max-w-7xl overflow-hidden rounded-[2rem] bg-gradient-to-br from-violet-950 via-violet-900 to-slate-950 p-8 text-white shadow-2xl shadow-violet-900/20 sm:p-12">
            <div class="grid items-center gap-10 lg:grid-cols-[1fr_0.9fr]">
                <div>
                    <div class="mb-8 flex h-20 w-20 items-center justify-center rounded-full bg-lime-300 text-4xl text-slate-950">🚀</div>
                    <h2 class="max-w-xl text-4xl font-black tracking-tight sm:text-5xl">Ready to level up your platform?</h2>
                    <p class="mt-5 max-w-2xl text-violet-100">Contact us to get a demo and see how our content can boost engagement, retention and revenue.</p>
                </div>

                <div class="flex flex-col items-start gap-5 lg:items-end">
                    <a href="mailto:sales@wrangle.win" class="inline-flex w-full items-center justify-center gap-4 rounded-xl bg-lime-300 px-8 py-5 text-base font-black text-slate-950 transition hover:bg-lime-200 sm:w-auto">
                        Request a Demo
                        <span class="text-2xl">→</span>
                    </a>
                    <a href="mailto:sales@wrangle.win" class="inline-flex w-full items-center justify-center rounded-full bg-violet-600 p-5 text-2xl text-white transition hover:bg-violet-500 sm:w-auto">
                        💬
                    </a>
                </div>
            </div>
        </div>
    </section>
</main>
</body>
</html>
