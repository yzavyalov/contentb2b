<?php

use App\Enums\BetStatus;
use App\Enums\UserRole;
use App\Models\Bet;
use Carbon\Carbon;
use Livewire\Component;
use App\Jobs\ResolveBetWithAi;


new class extends Component
{
    public string $period = '30';


    public function with(): array
    {
        $user = auth()->user();

        /*
        |--------------------------------------------------------------------------
        | BASE QUERY
        |--------------------------------------------------------------------------
        */

        $base = Bet::query();

        /*
         * Content Manager видит только свои данные.
         */
        if ($user->role === UserRole::CONTENT_MANAGER) {
            $base->where(
                'created_by_user_id',
                $user->id
            );
        }


        /*
        |--------------------------------------------------------------------------
        | TOTAL COUNTERS
        |--------------------------------------------------------------------------
        */

        $total = (clone $base)->count();

        $draft = (clone $base)
            ->where('status', BetStatus::DRAFT->value)
            ->count();

        $pending = (clone $base)
            ->where('status', BetStatus::PENDING_REVIEW->value)
            ->count();

        $approved = (clone $base)
            ->whereNotNull('approved_at')
            ->count();

        $rejected = (clone $base)
            ->whereNotNull('rejected_at')
            ->count();

        $published = (clone $base)
            ->whereNotNull('published_at')
            ->count();

        $resolved = (clone $base)
            ->whereNotNull('resolved_at')
            ->count();


        /*
        |--------------------------------------------------------------------------
        | PERIOD
        |--------------------------------------------------------------------------
        */

        $days = in_array(
            $this->period,
            ['7', '14', '30', '90'],
            true
        )
            ? (int) $this->period
            : 30;


        $startDate = now()
            ->startOfDay()
            ->subDays($days - 1);


        /*
        |--------------------------------------------------------------------------
        | DAILY DATA
        |--------------------------------------------------------------------------
        */

        $createdRows = (clone $base)
            ->where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');


        $approvedRows = (clone $base)
            ->whereNotNull('approved_at')
            ->where('approved_at', '>=', $startDate)
            ->selectRaw('DATE(approved_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');


        $rejectedRows = (clone $base)
            ->whereNotNull('rejected_at')
            ->where('rejected_at', '>=', $startDate)
            ->selectRaw('DATE(rejected_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');


        $publishedRows = (clone $base)
            ->whereNotNull('published_at')
            ->where('published_at', '>=', $startDate)
            ->selectRaw('DATE(published_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');


        $daily = [];


        for ($i = 0; $i < $days; $i++) {

            $date = $startDate
                ->copy()
                ->addDays($i);

            $key = $date->format('Y-m-d');


            $daily[] = [

                'date' => $key,

                'label' => $date->format(
                    $days > 30
                        ? 'd M'
                        : 'd M'
                ),

                'created' => (int) (
                    $createdRows[$key] ?? 0
                ),

                'approved' => (int) (
                    $approvedRows[$key] ?? 0
                ),

                'rejected' => (int) (
                    $rejectedRows[$key] ?? 0
                ),

                'published' => (int) (
                    $publishedRows[$key] ?? 0
                ),

            ];

        }


        /*
         * Нужен для вычисления высоты столбцов.
         */
        $maxDaily = max(
            1,
            collect($daily)
                ->flatMap(fn ($row) => [
                    $row['created'],
                    $row['approved'],
                    $row['rejected'],
                    $row['published'],
                ])
                ->max()
        );


        /*
        |--------------------------------------------------------------------------
        | STATUS DISTRIBUTION
        |--------------------------------------------------------------------------
        */

        $statusStats = [];

        foreach (BetStatus::cases() as $status) {

            $count = (clone $base)
                ->where(
                    'status',
                    $status->value
                )
                ->count();


            $statusStats[] = [

                'value' => $status->value,

                'label' => $status->label(),

                'count' => $count,

                'percentage' =>
                    $total > 0
                        ? round(($count / $total) * 100, 1)
                        : 0,

            ];

        }


        /*
        |--------------------------------------------------------------------------
        | RECENT BETS
        |--------------------------------------------------------------------------
        */

        $recentBets = (clone $base)
            ->with([
                'translations',
                'creator',
                'supervisor',
            ])
            ->latest('created_at')
            ->limit(7)
            ->get();


        return [

            'total' => $total,

            'draft' => $draft,

            'pending' => $pending,

            'approved' => $approved,

            'rejected' => $rejected,

            'published' => $published,

            'resolved' => $resolved,

            'daily' => $daily,

            'maxDaily' => $maxDaily,

            'statusStats' => $statusStats,

            'recentBets' => $recentBets,

            'isManager' =>
                $user->role === UserRole::CONTENT_MANAGER,

            'canCreateBet' =>
                $user->role === UserRole::CONTENT_MANAGER
                || $user->role === UserRole::CONTENT_SUPERVISOR
                || $user->role === UserRole::ADMIN,

        ];
    }


    public function startFinishedBetCheck(): void
    {
        $user = auth()->user();

        abort_unless(
            $user
            && (
                $user->isAdmin()
                || $user->isContentSupervisor()
            ),
            403
        );

        $queued = 0;

        Bet::query()
            ->whereNotNull('finish_at')
            ->where('finish_at', '<=', now())
            ->where('status', BetStatus::PUBLISHED->value)
            ->select('id')
            ->orderBy('id')
            ->chunkById(100, function ($bets) use (&$queued) {
                foreach ($bets as $bet) {
                    $updated = Bet::query()
                        ->whereKey($bet->id)
                        ->where('status', BetStatus::PUBLISHED->value)
                        ->update([
                            'status' => BetStatus::RESOLVING->value,
                        ]);

                    if (! $updated) {
                        continue;
                    }

                    ResolveBetWithAi::dispatch($bet->id);

                    $queued++;
                }
            });

        session()->flash(
            'status',
            $queued > 0
                ? $queued . ' finished bets sent for AI resolution.'
                : 'No finished published bets require resolution.'
        );
    }

};

?>
<div>
    <style>
        .wr-card {
            background: var(--wr-panel);
            border: 1px solid var(--wr-border);
            box-shadow: var(--wr-shadow);
        }

        .wr-title { color: var(--wr-text); }
        .wr-soft { color: var(--wr-text-soft); }
        .wr-muted { color: var(--wr-muted); }
        .wr-muted-soft { color: var(--wr-muted-soft); }

        .wr-input {
            border: 1px solid var(--wr-border);
            background: var(--wr-input);
            color: var(--wr-text);
        }

        .wr-input:focus {
            outline: none;
            border-color: rgba(163, 230, 53, .65);
            box-shadow: 0 0 0 3px rgba(163, 230, 53, .10);
        }

        .wr-divider {
            border-color: var(--wr-border);
        }

        .wr-progress-track {
            background: var(--wr-panel-secondary);
        }

        .wr-recent-row + .wr-recent-row {
            border-top: 1px solid var(--wr-border);
        }

        .wr-status-pill {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            border: 1px solid var(--wr-border);
            background: var(--wr-panel-secondary);
            padding: .35rem .65rem;
            font-size: 10px;
            line-height: 1;
            font-weight: 800;
            color: var(--wr-text-soft);
            white-space: nowrap;
        }
    </style>

    {{-- HEADER --}}
    <div class="mb-7 flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
        <div>
            <div class="text-xs font-black uppercase tracking-[0.18em] text-lime-400">
                Content
            </div>

            <h1 class="wr-title mt-2 text-3xl font-black tracking-tight">
                Markets
            </h1>

            <p class="wr-muted mt-2 text-sm">
                @if($isManager)
                    Statistics for your prediction markets.
                @else
                    Prediction market content performance and moderation overview.
                @endif
            </p>
        </div>

        <div class="flex flex-wrap gap-3">
            <a
                href="{{ route('content.bets.index') }}"
                class="wr-card inline-flex items-center justify-center rounded-xl px-5 py-3 text-sm font-black wr-title transition hover:border-lime-400/40 hover:text-lime-400"
            >
                View Bets →
            </a>

            @if($canCreateBet)
                <a
                    href="{{ route('content.bets.create') }}"
                    class="inline-flex items-center justify-center rounded-xl bg-lime-400 px-5 py-3 text-sm font-black text-[#07111f] transition hover:bg-lime-300"
                >
                    + Create Bet
                </a>
            @endif
        </div>
    </div>

    {{-- MAIN STATS --}}
    <div class="mb-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">

        <a
            href="{{ route('content.bets.index') }}"
            class="wr-card group rounded-2xl p-5 transition hover:border-lime-400/30"
        >
            <div class="wr-muted-soft text-[10px] font-black uppercase tracking-[0.14em]">
                Total Bets
            </div>

            <div class="wr-title mt-3 text-4xl font-black">
                {{ number_format($total) }}
            </div>

            <div class="mt-3 text-xs font-bold text-lime-500 opacity-0 transition group-hover:opacity-100">
                View all →
            </div>
        </a>

        <a
            href="{{ route('content.bets.index', ['status' => 'pending_review']) }}"
            class="wr-card rounded-2xl p-5 transition hover:border-amber-400/30"
        >
            <div class="wr-muted-soft text-[10px] font-black uppercase tracking-[0.14em]">
                Waiting Review
            </div>

            <div class="mt-3 text-4xl font-black text-amber-400">
                {{ number_format($pending) }}
            </div>

            <div class="wr-muted mt-3 text-xs font-semibold">
                Needs supervisor attention
            </div>
        </a>

        <div class="wr-card rounded-2xl p-5">
            <div class="wr-muted-soft text-[10px] font-black uppercase tracking-[0.14em]">
                Approved
            </div>

            <div class="mt-3 text-4xl font-black text-lime-500">
                {{ number_format($approved) }}
            </div>

            <div class="wr-muted mt-3 text-xs">
                Successfully reviewed
            </div>
        </div>

        <div class="wr-card rounded-2xl p-5">
            <div class="wr-muted-soft text-[10px] font-black uppercase tracking-[0.14em]">
                Published
            </div>

            <div class="mt-3 text-4xl font-black text-sky-400">
                {{ number_format($published) }}
            </div>

            <div class="wr-muted mt-3 text-xs">
                Delivered content
            </div>
        </div>
    </div>

    {{-- SECONDARY STATS --}}
    <div class="mb-5 grid gap-4 md:grid-cols-3">
        <div class="wr-card rounded-2xl px-5 py-4">
            <div class="wr-muted-soft text-[10px] font-black uppercase tracking-[0.12em]">
                Drafts
            </div>

            <div class="wr-title mt-2 text-2xl font-black">
                {{ $draft }}
            </div>
        </div>

        <div class="wr-card rounded-2xl px-5 py-4">
            <div class="wr-muted-soft text-[10px] font-black uppercase tracking-[0.12em]">
                Rejected
            </div>

            <div class="mt-2 text-2xl font-black text-red-400">
                {{ $rejected }}
            </div>
        </div>

        <div class="wr-card rounded-2xl px-5 py-4">
            <div class="wr-muted-soft text-[10px] font-black uppercase tracking-[0.12em]">
                Resolved
            </div>

            <div class="wr-title mt-2 text-2xl font-black">
                {{ $resolved }}
            </div>
        </div>
    </div>

    {{-- DAILY ACTIVITY --}}
    <section class="wr-card mb-5 rounded-2xl p-6">
        <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <div class="text-xs font-black uppercase tracking-[0.16em] text-lime-400">
                    Activity
                </div>

                <h2 class="wr-title mt-2 text-xl font-black">
                    Bets by Day
                </h2>

                <p class="wr-muted mt-1 text-sm">
                    Creation, approval, rejection and publication activity.
                </p>
            </div>

            <select
                wire:model.live="period"
                class="wr-input rounded-xl px-4 py-2.5 text-sm font-bold"
            >
                <option value="7">Last 7 days</option>
                <option value="14">Last 14 days</option>
                <option value="30">Last 30 days</option>
                <option value="90">Last 90 days</option>
            </select>
        </div>

        {{-- LEGEND --}}
        <div class="mb-5 flex flex-wrap gap-5">
            <div class="flex items-center gap-2">
                <div class="h-2.5 w-2.5 rounded-full bg-slate-400"></div>
                <span class="wr-muted text-xs font-bold">Created</span>
            </div>

            <div class="flex items-center gap-2">
                <div class="h-2.5 w-2.5 rounded-full bg-lime-400"></div>
                <span class="wr-muted text-xs font-bold">Approved</span>
            </div>

            <div class="flex items-center gap-2">
                <div class="h-2.5 w-2.5 rounded-full bg-red-400"></div>
                <span class="wr-muted text-xs font-bold">Rejected</span>
            </div>

            <div class="flex items-center gap-2">
                <div class="h-2.5 w-2.5 rounded-full bg-sky-400"></div>
                <span class="wr-muted text-xs font-bold">Published</span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <div
                class="flex h-72 min-w-[900px] items-end gap-2 border-b"
                style="border-color: var(--wr-border);"
            >
                @foreach($daily as $day)
                    <div
                        class="group flex h-full min-w-[22px] flex-1 items-end justify-center gap-[2px]"
                        title="{{ $day['label'] }} — Created: {{ $day['created'] }}, Approved: {{ $day['approved'] }}, Rejected: {{ $day['rejected'] }}, Published: {{ $day['published'] }}"
                    >
                        <div
                            class="w-1/4 min-w-[3px] rounded-t bg-slate-400/70 transition group-hover:bg-slate-300"
                            style="height: {{ max(2, ($day['created'] / $maxDaily) * 100) }}%;"
                        ></div>

                        <div
                            class="w-1/4 min-w-[3px] rounded-t bg-lime-400"
                            style="height: {{ max(2, ($day['approved'] / $maxDaily) * 100) }}%;"
                        ></div>

                        <div
                            class="w-1/4 min-w-[3px] rounded-t bg-red-400/80"
                            style="height: {{ max(2, ($day['rejected'] / $maxDaily) * 100) }}%;"
                        ></div>

                        <div
                            class="w-1/4 min-w-[3px] rounded-t bg-sky-400/80"
                            style="height: {{ max(2, ($day['published'] / $maxDaily) * 100) }}%;"
                        ></div>
                    </div>
                @endforeach
            </div>

            <div class="mt-3 flex min-w-[900px] gap-2">
                @foreach($daily as $index => $day)
                    <div class="wr-muted-soft min-w-[22px] flex-1 text-center text-[9px]">
                        @if(count($daily) <= 14 || $index % 5 === 0)
                            {{ $day['label'] }}
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- STATUS + RECENT --}}
    <div class="grid gap-5 xl:grid-cols-2">

        {{-- STATUS DISTRIBUTION --}}
        <section class="wr-card rounded-2xl p-6">
            <div class="text-xs font-black uppercase tracking-[0.16em] text-lime-400">
                Status
            </div>

            <h2 class="wr-title mt-2 text-xl font-black">
                Bet Distribution
            </h2>

            <div class="mt-6 space-y-5">
                @foreach($statusStats as $row)
                    <div>
                        <div class="mb-2 flex items-center justify-between gap-4">
                            <div class="wr-soft text-sm font-bold">
                                {{ $row['label'] }}
                            </div>

                            <div class="wr-title text-sm font-black">
                                {{ $row['count'] }}
                            </div>
                        </div>

                        <div class="wr-progress-track h-2 overflow-hidden rounded-full">
                            <div
                                class="h-full rounded-full bg-lime-400"
                                style="width: {{ $row['percentage'] }}%;"
                            ></div>
                        </div>

                        <div class="wr-muted-soft mt-1 text-right text-[10px]">
                            {{ $row['percentage'] }}%
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- RECENT --}}
        <section class="wr-card rounded-2xl p-6">
            <div class="flex items-start justify-between gap-5">
                <div>
                    <div class="text-xs font-black uppercase tracking-[0.16em] text-lime-400">
                        Recent
                    </div>

                    <h2 class="wr-title mt-2 text-xl font-black">
                        Latest Bets
                    </h2>
                </div>

                <a
                    href="{{ route('content.bets.index') }}"
                    class="text-xs font-black text-lime-500 hover:text-lime-400"
                >
                    View all →
                </a>
            </div>

            <div class="mt-5">
                @forelse($recentBets as $bet)
                    @php
                        $translation =
                            $bet->translations->firstWhere('locale', 'en')
                            ?? $bet->translations->first();
                    @endphp

                    <div class="wr-recent-row py-4 first:pt-0">
                        <div class="flex items-start justify-between gap-5">
                            <div class="min-w-0">
                                <div class="wr-title truncate font-bold">
                                    {{ $translation?->title ?? 'Untitled Bet' }}
                                </div>

                                <div class="wr-muted-soft mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs">
                                    <span>#{{ $bet->id }}</span>
                                    <span>{{ $bet->creator?->email }}</span>
                                    <span>{{ $bet->created_at->format('d.m.Y H:i') }}</span>
                                </div>
                            </div>

                            <span class="wr-status-pill">
                                {{ $bet->status->label() }}
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="wr-muted-soft py-12 text-center text-sm">
                        No bets yet.
                    </div>
                @endforelse
            </div>
        </section>
    </div>
</div>
