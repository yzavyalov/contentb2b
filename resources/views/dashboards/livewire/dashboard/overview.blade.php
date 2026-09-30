<div class="space-y-6">
    <section class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">Dashboard</h1>
            <p class="mt-2 text-sm text-slate-500">
                Welcome back. Here’s what’s happening with your integration.
            </p>
        </div>

        <button class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm">
            May 1 – May 31, 2026
        </button>
    </section>

    {{-- Stats --}}
    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($stats as $index => $stat)
            @php
                $iconBg = match($index) {
                    0 => 'bg-lime-100 text-emerald-700',
                    1 => 'bg-blue-100 text-blue-700',
                    2 => 'bg-violet-100 text-violet-700',
                    default => 'bg-orange-100 text-orange-700',
                };
            @endphp

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start gap-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl {{ $iconBg }} text-lg font-black">
                        {{ $stat['icon'] }}
                    </div>

                    <div>
                        <div class="text-xs font-bold text-slate-500">{{ $stat['label'] }}</div>
                        <div class="mt-1 text-3xl font-black tracking-tight text-slate-950">{{ $stat['value'] }}</div>
                        <div class="mt-1 text-xs text-slate-400">{{ $stat['meta'] }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </section>

    <section class="grid gap-6 xl:grid-cols-[1.45fr_0.85fr]">
        {{-- Chart --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-base font-black text-slate-950">Market Activity</h2>
                    <p class="mt-1 text-xs text-slate-500">Markets delivered during the selected period.</p>
                </div>
                <span class="rounded-full bg-lime-100 px-3 py-1 text-xs font-black text-emerald-700">+18.4%</span>
            </div>

            @php
                $max = max($activity);
                $points = [];
                $count = count($activity);
                foreach ($activity as $i => $v) {
                    $x = $count > 1 ? ($i / ($count - 1)) * 100 : 0;
                    $y = 100 - (($v / $max) * 82 + 8);
                    $points[] = round($x, 2) . ',' . round($y, 2);
                }
            @endphp

            <div class="mt-8 h-[280px] w-full overflow-hidden rounded-xl bg-gradient-to-b from-slate-50 to-white p-3">
                <svg viewBox="0 0 100 100" class="h-full w-full" preserveAspectRatio="none">
                    @for ($i = 20; $i <= 80; $i += 20)
                        <line x1="0" y1="{{ $i }}" x2="100" y2="{{ $i }}" stroke="#e2e8f0" stroke-width="0.5"/>
                    @endfor

                    <polyline
                        points="{{ implode(' ', $points) }}"
                        fill="none"
                        stroke="#84cc16"
                        stroke-width="1.5"
                        vector-effect="non-scaling-stroke"
                    />

                    <polyline
                        points="0,100 {{ implode(' ', $points) }} 100,100"
                        fill="rgba(132,204,22,.08)"
                        stroke="none"
                    />
                </svg>
            </div>

            <div class="mt-3 flex justify-between text-[11px] text-slate-400">
                <span>May 1</span>
                <span>May 8</span>
                <span>May 15</span>
                <span>May 22</span>
                <span>May 31</span>
            </div>
        </div>

        {{-- Recent settlements --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-black text-slate-950">Recent Settlements</h2>
                <a href="{{ \Illuminate\Support\Facades\Route::has('dashboard.markets') ? route('dashboard.markets') : '#' }}"
                   class="text-xs font-black text-violet-600 hover:text-violet-500">
                    View all
                </a>
            </div>

            <div class="mt-5 divide-y divide-slate-100">
                @foreach ($recentSettlements as $settlement)
                    <div class="flex gap-3 py-4">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-xl">
                            {{ $settlement['icon'] }}
                        </div>

                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-bold leading-5 text-slate-900">
                                {{ $settlement['question'] }}
                            </p>

                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                <span class="rounded-md bg-violet-50 px-2 py-1 text-[9px] font-black text-violet-600">
                                    {{ $settlement['category'] }}
                                </span>
                                <span class="text-[11px] text-slate-400">{{ $settlement['ago'] }}</span>
                            </div>
                        </div>

                        <div class="text-xs font-black {{ $settlement['result'] === 'YES' ? 'text-emerald-600' : 'text-rose-500' }}">
                            {{ $settlement['result'] }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Quick start --}}
    <section class="grid gap-6 xl:grid-cols-[1fr_280px]">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <h2 class="text-base font-black text-slate-950">Quick Start</h2>
            <p class="mt-1 text-xs text-slate-500">Complete these steps to go live.</p>

            <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ([
                    ['1', 'Get API Key', 'Create your API credentials', true],
                    ['2', 'Set Up Webhook', 'Configure your callback URL', true],
                    ['3', 'Test Integration', 'Test the API and webhooks', true],
                    ['4', 'Go Live', 'Start receiving markets', false],
                ] as [$num, $title, $desc, $done])
                    <div class="rounded-xl border border-slate-200 p-4">
                        <div class="flex h-8 w-8 items-center justify-center rounded-full border {{ $done ? 'border-lime-400 bg-lime-50 text-emerald-700' : 'border-slate-300 text-slate-400' }} text-xs font-black">
                            {{ $done ? '✓' : $num }}
                        </div>
                        <div class="mt-4 text-sm font-black text-slate-950">{{ $title }}</div>
                        <div class="mt-1 text-xs leading-5 text-slate-500">{{ $desc }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-violet-50 text-2xl">▣</div>
            <h2 class="mt-5 text-base font-black text-slate-950">Documentation</h2>
            <p class="mt-2 text-sm leading-6 text-slate-500">Need help with integration?</p>
            <a href="#" class="mt-5 inline-flex rounded-xl border border-slate-200 px-4 py-3 text-sm font-black text-slate-700 hover:bg-slate-50">
                View Docs →
            </a>
        </div>
    </section>
</div>
