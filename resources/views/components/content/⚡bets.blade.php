<?php

use App\Enums\UserRole;
use App\Models\Bet;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use App\Enums\BetStatus;
use Illuminate\Support\Facades\Artisan;


new class extends Component
{
    use WithPagination;

    public string $search = '';

    public string $status = '';

    public string $creator = '';

    public string $supervisor = '';

    public string $sortField = 'created_at';

    public string $sortDirection = 'desc';


    public function updatedSearch(): void
    {
        $this->resetPage();
    }


    public function updatedStatus(): void
    {
        $this->resetPage();
    }


    public function updatedCreator(): void
    {
        $this->resetPage();
    }


    public function updatedSupervisor(): void
    {
        $this->resetPage();
    }


    public function sortBy(string $field): void
    {
        $allowed = [
            'created_at',
            'finish_at',
            'status',
            'created_by_user_id',
            'supervisor_user_id',
        ];

        if (! in_array($field, $allowed, true)) {
            return;
        }

        if ($this->sortField === $field) {
            $this->sortDirection =
                $this->sortDirection === 'asc'
                    ? 'desc'
                    : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }


    public function with(): array
    {
        $currentUser = auth()->user();


        $query = Bet::query()

            ->with([
                'creator',
                'supervisor',
                'translations',
                'answers.translations',
                'categories',
                'countries',
                'countryGroups',
                'aiResolution',
            ]);


        /*
         * Content Manager видит только свои Bet.
         */
        if ($currentUser->role === UserRole::CONTENT_MANAGER) {
            $query->where(
                'created_by_user_id',
                $currentUser->id
            );
        }


        /*
         * SEARCH
         */
        $search = trim($this->search);

        if ($search !== '') {

            $query->where(function ($query) use ($search) {

                /*
                 * Market ID
                 */
                if (ctype_digit($search)) {
                    $query->whereKey((int) $search)
                        ->orWhereHas(
                            'translations',
                            function ($query) use ($search) {
                                $query
                                    ->where('title', 'like', '%' . $search . '%')
                                    ->orWhere('description', 'like', '%' . $search . '%');
                            }
                        );
                } else {
                    $query->whereHas(
                        'translations',
                        function ($query) use ($search) {
                            $query
                                ->where('title', 'like', '%' . $search . '%')
                                ->orWhere('description', 'like', '%' . $search . '%');
                        }
                    );
                }


                /*
                 * Ответы
                 */
                $query->orWhereHas(
                    'answers.translations',
                    function ($query) use ($search) {

                        $query->where(
                            'title',
                            'like',
                            '%' . $search . '%'
                        );

                    }
                );


                /*
                 * Категории
                 */
                $query->orWhereHas(
                    'categories',
                    function ($query) use ($search) {

                        $query
                            ->where(
                                'name',
                                'like',
                                '%' . $search . '%'
                            )
                            ->orWhere(
                                'slug',
                                'like',
                                '%' . $search . '%'
                            );

                    }
                );


                /*
                 * Страны
                 */
                $query->orWhereHas(
                    'countries',
                    function ($query) use ($search) {

                        $query
                            ->where(
                                'name',
                                'like',
                                '%' . $search . '%'
                            )
                            ->orWhere(
                                'code',
                                'like',
                                '%' . $search . '%'
                            );

                    }
                );


                /*
                 * Группы стран:
                 * EU, Europe, LATAM и т.д.
                 */
                $query->orWhereHas(
                    'countryGroups',
                    function ($query) use ($search) {

                        $query
                            ->where(
                                'name',
                                'like',
                                '%' . $search . '%'
                            )
                            ->orWhere(
                                'slug',
                                'like',
                                '%' . $search . '%'
                            );

                    }
                );


                /*
                 * Кто создал
                 */
                $query->orWhereHas(
                    'creator',
                    function ($query) use ($search) {

                        $query
                            ->where(
                                'name',
                                'like',
                                '%' . $search . '%'
                            )
                            ->orWhere(
                                'email',
                                'like',
                                '%' . $search . '%'
                            );

                    }
                );


                /*
                 * Supervisor
                 */
                $query->orWhereHas(
                    'supervisor',
                    function ($query) use ($search) {

                        $query
                            ->where(
                                'name',
                                'like',
                                '%' . $search . '%'
                            )
                            ->orWhere(
                                'email',
                                'like',
                                '%' . $search . '%'
                            );

                    }
                );

            });
        }


        /*
         * STATUS
         */
        if ($this->status !== '') {

            $query->where(
                'status',
                $this->status
            );

        }


        /*
         * CREATOR
         */
        if ($this->creator !== '') {

            $query->where(
                'created_by_user_id',
                $this->creator
            );

        }


        /*
         * SUPERVISOR
         */
        if ($this->supervisor !== '') {

            $query->where(
                'supervisor_user_id',
                $this->supervisor
            );

        }


        /*
         * SORT
         */
        $query->orderBy(
            $this->sortField,
            $this->sortDirection
        );


        /*
         * Для фильтров пользователей.
         */
        $creators = User::query()
            ->whereHas('createdBets')
            ->orderBy('name')
            ->get();


        $supervisors = User::query()
            ->whereHas('supervisedBets')
            ->orderBy('name')
            ->get();


        return [

            'bets' => $query->paginate(25),

            'creators' => $creators,

            'supervisors' => $supervisors,

            'canCreateBet' =>
                $currentUser->role === UserRole::CONTENT_MANAGER
                || $currentUser->role === UserRole::CONTENT_SUPERVISOR
                || $currentUser->role === UserRole::ADMIN,

            'canRunResolutionCheck' =>
                $currentUser->role === UserRole::CONTENT_SUPERVISOR
                || $currentUser->role === UserRole::ADMIN,

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

        /*
         * Use the same command as the scheduler.
         *
         * This keeps the manual UI action and automatic processing
         * on exactly the same lifecycle:
         *
         * published -> resolving
         * ResolveBetWithAi
         * SendMarketResolvingToMerchant
         */
        $exitCode = Artisan::call('bets:process-finished');

        if ($exitCode !== 0) {
            session()->flash(
                'status',
                'Finished market processing failed. Please check the application logs.'
            );

            return;
        }

        $output = trim(Artisan::output());

        preg_match(
            '/Markets moved to resolving:\s*(\d+)/',
            $output,
            $matches
        );

        $processed = isset($matches[1])
            ? (int) $matches[1]
            : 0;

        session()->flash(
            'status',
            $processed > 0
                ? $processed . ' finished markets moved to resolving.'
                : 'No finished bets require resolution.'
        );
    }


    public function withdrawFromReview(int $betId): void
    {
        $bet = Bet::query()->findOrFail($betId);

        $user = auth()->user();

        abort_unless(
            $user->isAdmin()
            || (
                $user->isContentManager()
                && $bet->created_by_user_id === $user->id
            ),
            403
        );

        abort_unless(
            $bet->status === BetStatus::PENDING_REVIEW,
            403
        );

        $bet->update([
            'status' => BetStatus::DRAFT,
            'supervisor_user_id' => null,
            'approved_at' => null,
            'rejected_at' => null,
        ]);

        session()->flash(
            'status',
            'Bet returned to draft.'
        );
    }
};

?>
<style>
    .wr-bets-card {
        background: var(--wr-panel);
        border: 1px solid var(--wr-border);
        box-shadow: 0 10px 30px rgba(2, 8, 23, .06);
    }

    html[data-theme="dark"] .wr-bets-card {
        box-shadow: none;
    }

    .wr-bets-input {
        width: 100%;
        border: 1px solid var(--wr-border);
        background: var(--wr-input);
        color: var(--wr-text);
        transition: border-color .15s ease, box-shadow .15s ease, background-color .15s ease;
    }

    .wr-bets-input:focus {
        outline: none;
        border-color: rgba(163, 230, 53, .65);
        box-shadow: 0 0 0 3px rgba(163, 230, 53, .10);
    }

    .wr-bets-input::placeholder {
        color: var(--wr-muted-soft);
    }

    .wr-bets-row {
        border-top: 1px solid var(--wr-border);
        transition: background-color .15s ease;
    }

    .wr-bets-row:hover {
        background: var(--wr-hover);
    }

    .wr-bets-th {
        color: var(--wr-muted-soft);
        font-size: 10px;
        font-weight: 900;
        letter-spacing: .13em;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .wr-bets-title {
        color: var(--wr-text);
    }

    .wr-bets-muted {
        color: var(--wr-muted);
    }

    .wr-bets-muted-soft {
        color: var(--wr-muted-soft);
    }

    .wr-status {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        border-radius: 999px;
        padding: .35rem .65rem;
        font-size: 11px;
        line-height: 1;
        font-weight: 800;
        white-space: nowrap;
        border: 1px solid transparent;
    }

    .wr-status-neutral {
        color: var(--wr-text-soft);
        border-color: var(--wr-border);
        background: var(--wr-panel-secondary);
    }

    .wr-status-draft {
        color: #94a3b8;
        border-color: rgba(148,163,184,.22);
        background: rgba(148,163,184,.08);
    }

    .wr-status-review {
        color: #f59e0b;
        border-color: rgba(245,158,11,.25);
        background: rgba(245,158,11,.09);
    }

    .wr-status-approved {
        color: #22c55e;
        border-color: rgba(34,197,94,.25);
        background: rgba(34,197,94,.09);
    }

    .wr-status-published {
        color: #38bdf8;
        border-color: rgba(56,189,248,.25);
        background: rgba(56,189,248,.09);
    }

    .wr-status-resolving {
        color: #a78bfa;
        border-color: rgba(167,139,250,.25);
        background: rgba(167,139,250,.09);
    }

    .wr-status-resolved {
        color: #84cc16;
        border-color: rgba(132,204,22,.28);
        background: rgba(132,204,22,.10);
    }

    .wr-status-rejected,
    .wr-status-failed {
        color: #fb7185;
        border-color: rgba(251,113,133,.25);
        background: rgba(251,113,133,.09);
    }

    .wr-chip {
        display: inline-flex;
        align-items: center;
        border-radius: .5rem;
        padding: .25rem .5rem;
        font-size: 10px;
        line-height: 1.15;
        font-weight: 800;
        color: var(--wr-text-soft);
        background: var(--wr-panel-secondary);
        border: 1px solid var(--wr-border);
    }
</style>

<div>

    {{-- HEADER --}}
    <div class="mb-7 flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
        <div>
            <div class="text-xs font-black uppercase tracking-[0.18em] text-lime-400">
                Content
            </div>

            <h1 class="mt-2 text-3xl font-black tracking-tight wr-bets-title">
                Prediction Markets
            </h1>

            <p class="mt-2 text-sm wr-bets-muted">
                @if(auth()->user()->role === \App\Enums\UserRole::CONTENT_MANAGER)
                    Your prediction markets.
                @else
                    All prediction markets created by the content team.
                @endif
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            @if($canRunResolutionCheck)
                <button
                    type="button"
                    wire:click="startFinishedBetCheck"
                    wire:confirm="Check all finished bets and send them for AI resolution?"
                    wire:loading.attr="disabled"
                    wire:target="startFinishedBetCheck"
                    class="inline-flex items-center justify-center rounded-xl border border-lime-400/25 bg-lime-400/10 px-5 py-3 text-sm font-black text-lime-400 transition hover:bg-lime-400/15 disabled:cursor-wait disabled:opacity-50"
                >
                    <span wire:loading.remove wire:target="startFinishedBetCheck">
                        Check Finished Bets
                    </span>
                    <span wire:loading wire:target="startFinishedBetCheck">
                        Starting...
                    </span>
                </button>
            @endif

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


    {{-- FILTERS --}}
    <div class="wr-bets-card mb-5 rounded-2xl p-4">
        <div class="grid gap-3 xl:grid-cols-12">

            <div class="xl:col-span-5">
                <label class="mb-1.5 block text-[10px] font-black uppercase tracking-[0.13em] wr-bets-muted-soft">
                    Search
                </label>

                <input
                    type="text"
                    wire:model.live.debounce.400ms="search"
                    placeholder="Title, answer, country, category, user..."
                    class="wr-bets-input rounded-xl px-4 py-2.5 text-sm"
                >
            </div>

            <div class="xl:col-span-2">
                <label class="mb-1.5 block text-[10px] font-black uppercase tracking-[0.13em] wr-bets-muted-soft">
                    Status
                </label>

                <select
                    wire:model.live="status"
                    class="wr-bets-input rounded-xl px-3 py-2.5 text-sm"
                >
                    <option value="">All statuses</option>

                    @foreach(\App\Enums\BetStatus::cases() as $betStatus)
                        <option value="{{ $betStatus->value }}">
                            {{ $betStatus->label() }}
                        </option>
                    @endforeach
                </select>
            </div>

            @if(auth()->user()->role !== \App\Enums\UserRole::CONTENT_MANAGER)
                <div class="xl:col-span-2">
                    <label class="mb-1.5 block text-[10px] font-black uppercase tracking-[0.13em] wr-bets-muted-soft">
                        Created By
                    </label>

                    <select
                        wire:model.live="creator"
                        class="wr-bets-input rounded-xl px-3 py-2.5 text-sm"
                    >
                        <option value="">All creators</option>

                        @foreach($creators as $creatorUser)
                            <option value="{{ $creatorUser->id }}">
                                {{ $creatorUser->email }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="xl:col-span-3">
                    <label class="mb-1.5 block text-[10px] font-black uppercase tracking-[0.13em] wr-bets-muted-soft">
                        Supervisor
                    </label>

                    <select
                        wire:model.live="supervisor"
                        class="wr-bets-input rounded-xl px-3 py-2.5 text-sm"
                    >
                        <option value="">All supervisors</option>

                        @foreach($supervisors as $supervisorUser)
                            <option value="{{ $supervisorUser->id }}">
                                {{ $supervisorUser->email }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif
        </div>
    </div>


    {{-- TABLE --}}
    <div class="wr-bets-card overflow-hidden rounded-2xl">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1280px] table-fixed">
                <colgroup>
                    <col style="width: 29%">
                    <col style="width: 9%">
                    <col style="width: 10%">
                    <col style="width: 15%">
                    <col style="width: 15%">
                    <col style="width: 9%">
                    <col style="width: 7%">
                    <col style="width: 6%">
                </colgroup>

                <thead style="background: var(--wr-panel-secondary);">
                <tr>
                    <th class="wr-bets-th px-5 py-3.5 text-left">Bet</th>

                    <th
                        wire:click="sortBy('status')"
                        class="wr-bets-th cursor-pointer px-4 py-3.5 text-left transition hover:text-lime-400"
                    >
                        Status
                    </th>

                    <th class="wr-bets-th px-4 py-3.5 text-left">
                        AI Status
                    </th>

                    <th
                        wire:click="sortBy('created_by_user_id')"
                        class="wr-bets-th cursor-pointer px-4 py-3.5 text-left transition hover:text-lime-400"
                    >
                        Created By
                    </th>

                    <th
                        wire:click="sortBy('supervisor_user_id')"
                        class="wr-bets-th cursor-pointer px-4 py-3.5 text-left transition hover:text-lime-400"
                    >
                        Supervisor
                    </th>

                    <th class="wr-bets-th px-4 py-3.5 text-left">
                        Geography
                    </th>

                    <th
                        wire:click="sortBy('finish_at')"
                        class="wr-bets-th cursor-pointer px-4 py-3.5 text-left transition hover:text-lime-400"
                    >
                        Finish
                    </th>

                    <th
                        wire:click="sortBy('created_at')"
                        class="wr-bets-th cursor-pointer px-4 py-3.5 text-left transition hover:text-lime-400"
                    >
                        Created
                    </th>
                </tr>
                </thead>

                <tbody>
                @forelse($bets as $bet)

                    @php
                        $translation =
                            $bet->translations->firstWhere('locale', 'en')
                            ?? $bet->translations->first();

                        $user = auth()->user();

                        $canEditBet =
                            $user->isAdmin()
                            || $user->isContentSupervisor()
                            || (
                                $user->isContentManager()
                                && $bet->created_by_user_id === auth()->id()
                                && in_array(
                                    $bet->status,
                                    [
                                        \App\Enums\BetStatus::DRAFT,
                                        \App\Enums\BetStatus::REJECTED,
                                    ],
                                    true
                                )
                            );

                        $statusClass = match($bet->status) {
                            \App\Enums\BetStatus::DRAFT => 'wr-status-draft',
                            \App\Enums\BetStatus::PENDING_REVIEW => 'wr-status-review',
                            \App\Enums\BetStatus::APPROVED => 'wr-status-approved',
                            \App\Enums\BetStatus::REJECTED => 'wr-status-rejected',
                            \App\Enums\BetStatus::PUBLISHED => 'wr-status-published',
                            \App\Enums\BetStatus::RESOLVING => 'wr-status-resolving',
                            \App\Enums\BetStatus::RESOLVED => 'wr-status-resolved',
                            default => 'wr-status-neutral',
                        };
                    @endphp

                    <tr
                        wire:key="bet-{{ $bet->id }}"
                        class="wr-bets-row"
                    >

                        {{-- BET --}}
                        <td class="px-5 py-4 align-top">
                            <div class="flex min-w-0 items-start gap-3">

                                @if($bet->image_path)
                                    <img
                                        src="{{ asset('storage/' . $bet->image_path) }}"
                                        alt=""
                                        class="h-11 w-14 shrink-0 rounded-lg object-cover"
                                    >
                                @endif

                                <div class="min-w-0">
                                    @if($canEditBet)
                                        <a
                                            href="{{ route('content.bets.edit', $bet) }}"
                                            class="group block"
                                        >
                                            <div class="wr-bets-title line-clamp-2 text-[15px] font-extrabold leading-5 transition group-hover:text-lime-400">
                                                {{ $translation?->title ?? 'Untitled Bet' }}
                                            </div>

                                            <div class="mt-1 text-[11px] wr-bets-muted-soft">
                                                #{{ $bet->id }}
                                            </div>
                                        </a>
                                    @else
                                        <div class="wr-bets-title line-clamp-2 text-[15px] font-extrabold leading-5">
                                            {{ $translation?->title ?? 'Untitled Bet' }}
                                        </div>

                                        <div class="mt-1 text-[11px] wr-bets-muted-soft">
                                            #{{ $bet->id }}
                                        </div>
                                    @endif

                                    @if(
                                        $bet->status === \App\Enums\BetStatus::PENDING_REVIEW
                                        && $user->isContentManager()
                                        && $bet->created_by_user_id === auth()->id()
                                    )
                                        <button
                                            type="button"
                                            wire:click="withdrawFromReview({{ $bet->id }})"
                                            wire:confirm="Return this bet to Draft?"
                                            class="mt-2 text-[11px] font-bold text-amber-400 transition hover:text-amber-300"
                                        >
                                            Withdraw from Review
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </td>

                        {{-- STATUS --}}
                        <td class="px-4 py-4 align-top">
                            <span class="wr-status {{ $statusClass }}">
                                {{ $bet->status->label() }}
                            </span>
                        </td>

                        {{-- AI STATUS --}}
                        <td class="px-4 py-4 align-top">
                            @if($bet->aiResolution)

                                @php
                                    $aiStatus = $bet->aiResolution->status;
                                @endphp

                                @if($aiStatus === 'completed')
                                    <span class="wr-status wr-status-resolved">Completed</span>
                                @elseif($aiStatus === 'manual_review')
                                    <span class="wr-status wr-status-review">Manual Review</span>
                                @elseif($aiStatus === 'processing')
                                    <span class="wr-status wr-status-resolving">Processing</span>
                                @elseif($aiStatus === 'failed')
                                    <span class="wr-status wr-status-failed">Failed</span>
                                @else
                                    <span class="wr-status wr-status-neutral">
                                        {{ ucfirst(str_replace('_', ' ', $aiStatus)) }}
                                    </span>
                                @endif

                            @elseif($bet->status === \App\Enums\BetStatus::RESOLVING)
                                <span class="wr-status wr-status-resolving">Queued</span>
                            @else
                                <span class="text-xs wr-bets-muted-soft">—</span>
                            @endif
                        </td>

                        {{-- CREATOR --}}
                        <td class="px-4 py-4 align-top">
                            <div class="truncate text-[13px] font-semibold wr-bets-title" title="{{ $bet->creator?->email ?? '' }}">
                                {{ $bet->creator?->email ?? '—' }}
                            </div>
                        </td>

                        {{-- SUPERVISOR --}}
                        <td class="px-4 py-4 align-top">
                            <div class="truncate text-[13px] wr-bets-muted" title="{{ $bet->supervisor?->email ?? '' }}">
                                {{ $bet->supervisor?->email ?? '—' }}
                            </div>
                        </td>

                        {{-- GEOGRAPHY --}}
                        <td class="px-4 py-4 align-top">
                            <div class="flex max-w-[160px] flex-wrap gap-1">
                                @foreach($bet->countryGroups->take(2) as $group)
                                    <span class="wr-chip">
                                        {{ $group->name }}
                                    </span>
                                @endforeach

                                @foreach($bet->countries->take(3) as $country)
                                    <span class="wr-chip">
                                        {{ $country->code }}
                                    </span>
                                @endforeach

                                @php
                                    $extraGeography =
                                        max(0, $bet->countryGroups->count() - 2)
                                        + max(0, $bet->countries->count() - 3);
                                @endphp

                                @if($extraGeography > 0)
                                    <span class="wr-chip">
                                        +{{ $extraGeography }}
                                    </span>
                                @endif

                                @if(
                                    $bet->countryGroups->isEmpty()
                                    && $bet->countries->isEmpty()
                                )
                                    <span class="text-xs wr-bets-muted">
                                        Global
                                    </span>
                                @endif
                            </div>
                        </td>

                        {{-- FINISH --}}
                        <td class="px-4 py-4 align-top">
                            <div class="text-[13px] font-semibold wr-bets-title">
                                {{ $bet->finish_at?->format('d.m.Y') ?? '—' }}
                            </div>

                            <div class="mt-0.5 text-[11px] wr-bets-muted-soft">
                                {{ $bet->finish_at?->format('H:i') }}
                            </div>
                        </td>

                        {{-- CREATED --}}
                        <td class="px-4 py-4 align-top">
                            <div class="text-[13px] wr-bets-muted">
                                {{ $bet->created_at->format('d.m.Y') }}
                            </div>

                            <div class="mt-0.5 text-[11px] wr-bets-muted-soft">
                                {{ $bet->created_at->format('H:i') }}
                            </div>
                        </td>

                    </tr>

                @empty

                    <tr>
                        <td
                            colspan="8"
                            class="px-6 py-20 text-center"
                        >
                            <div class="text-lg font-black wr-bets-title">
                                No bets found
                            </div>

                            <div class="mt-2 text-sm wr-bets-muted">
                                Try changing the search or filters.
                            </div>
                        </td>
                    </tr>

                @endforelse
                </tbody>
            </table>
        </div>
    </div>


    {{-- PAGINATION --}}
    @if($bets->hasPages())
        <div class="mt-5 flex flex-col gap-4 rounded-2xl border border-[var(--wr-border)] bg-[var(--wr-card)] px-5 py-4 sm:flex-row sm:items-center sm:justify-between">

            <div class="text-sm font-semibold text-[var(--wr-muted)]">
                Showing
                <span class="font-black text-[var(--wr-text)]">{{ $bets->firstItem() }}</span>
                –
                <span class="font-black text-[var(--wr-text)]">{{ $bets->lastItem() }}</span>
                of
                <span class="font-black text-[var(--wr-text)]">{{ $bets->total() }}</span>
                markets
            </div>

            <div class="flex flex-wrap items-center gap-2">

                {{-- Previous --}}
                @if($bets->onFirstPage())
                    <span class="cursor-not-allowed rounded-xl border border-[var(--wr-border)] px-4 py-2 text-sm font-bold opacity-40">
                        ← Previous
                    </span>
                @else
                    <button
                        type="button"
                        wire:click="previousPage"
                        wire:loading.attr="disabled"
                        class="rounded-xl border border-[var(--wr-border)] px-4 py-2 text-sm font-bold text-[var(--wr-text)] transition hover:border-lime-400/40 hover:text-lime-400"
                    >
                        ← Previous
                    </button>
                @endif


                {{-- Page numbers --}}
                @foreach(range(1, $bets->lastPage()) as $page)
                    @if($page === $bets->currentPage())
                        <span class="flex h-10 min-w-10 items-center justify-center rounded-xl bg-lime-400 px-3 text-sm font-black text-[#06111f]">
                            {{ $page }}
                        </span>
                    @else
                        <button
                            type="button"
                            wire:click="gotoPage({{ $page }})"
                            wire:loading.attr="disabled"
                            class="flex h-10 min-w-10 items-center justify-center rounded-xl border border-[var(--wr-border)] px-3 text-sm font-bold text-[var(--wr-text)] transition hover:border-lime-400/40 hover:text-lime-400"
                        >
                            {{ $page }}
                        </button>
                    @endif
                @endforeach


                {{-- Next --}}
                @if($bets->hasMorePages())
                    <button
                        type="button"
                        wire:click="nextPage"
                        wire:loading.attr="disabled"
                        class="rounded-xl border border-[var(--wr-border)] px-4 py-2 text-sm font-bold text-[var(--wr-text)] transition hover:border-lime-400/40 hover:text-lime-400"
                    >
                        Next →
                    </button>
                @else
                    <span class="cursor-not-allowed rounded-xl border border-[var(--wr-border)] px-4 py-2 text-sm font-bold opacity-40">
                        Next →
                    </span>
                @endif

            </div>
        </div>
    @endif

</div>
