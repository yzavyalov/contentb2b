<?php

use App\Enums\BetStatus;
use App\Models\Bet;
use App\Models\Category;
use App\Models\Country;
use App\Models\CountryGroup;
use Livewire\Component;
use Livewire\WithPagination;
use App\Jobs\SendMarketToMerchant;
use App\Services\CurrentMerchant;

new class extends Component
{
    use WithPagination;

    private function currentMerchant()
    {
        return app(CurrentMerchant::class)->get();
    }

    public string $search = '';
    public string $status = '';
    public string $category = '';
    public string $country = '';
    public string $countryGroup = '';
    public string $locale = '';
    public string $finishFrom = '';
    public string $finishTo = '';

    public array $selected = [];
    public bool $selectPage = false;
    public bool $selectAllMatching = false;

    public function updatedSearch(): void
    {
        $this->clearSelection();
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->clearSelection();
        $this->resetPage();
    }

    public function updatedCategory(): void
    {
        $this->clearSelection();
        $this->resetPage();
    }

    public function updatedCountry(): void
    {
        $this->clearSelection();
        $this->resetPage();
    }

    public function updatedCountryGroup(): void
    {
        $this->clearSelection();
        $this->resetPage();
    }

    public function updatedLocale(): void
    {
        $this->clearSelection();
        $this->resetPage();
    }

    public function updatedFinishFrom(): void
    {
        $this->clearSelection();
        $this->resetPage();
    }

    public function updatedFinishTo(): void
    {
        $this->clearSelection();
        $this->resetPage();
    }

    public function updatedSelectPage(bool $value): void
    {
        /*
         * Если включён глобальный Select all matching,
         * изменение selectPage не должно отменять его.
         */
        if ($this->selectAllMatching) {
            return;
        }

        $this->selected = [];

        if (! $value) {
            return;
        }

        $merchant = $this->currentMerchant();

        if (! $merchant) {
            $this->selectPage = false;

            return;
        }

        /*
         * ВАЖНО:
         * сначала получаем именно текущую страницу paginator,
         * а уже из неё выбираем разрешённые markets.
         */
        $currentPageMarkets = $this->markets()
            ->paginate(20, ['*'], 'page', $this->getPage());

        $this->selected = $currentPageMarkets
            ->getCollection()

            // Только Published
            ->filter(function ($market) {
                $status = $market->status instanceof \BackedEnum
                    ? $market->status->value
                    : $market->status;

                return $status === BetStatus::PUBLISHED->value;
            })

            // Исключаем уже успешно Delivered
            ->filter(function ($market) {
                $merchantBet = $market->merchantBets->first();

                return ! $merchantBet
                    || $merchantBet->delivered_at === null;
            })

            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->values()
            ->toArray();
    }

    public function selectAllMatchingMarkets(): void
    {
        $merchant = $this->currentMerchant();

        if (! $merchant) {
            $this->clearSelection();
            return;
        }

        // IDs current page are kept only for visual checkbox state.
        // selectAllMatching=true is the authoritative server-side "all filtered" mode.
        $currentPageMarkets = $this->markets()
            ->paginate(20, ['*'], 'page', $this->getPage());

        $this->selected = $currentPageMarkets
            ->getCollection()
            ->filter(function ($market) {
                $status = $market->status instanceof \BackedEnum
                    ? $market->status->value
                    : $market->status;

                return $status === BetStatus::PUBLISHED->value;
            })
            ->filter(function ($market) {
                $merchantBet = $market->merchantBets->first();

                return ! $merchantBet || $merchantBet->delivered_at === null;
            })
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->values()
            ->toArray();

        $this->selectAllMatching = true;
        $this->selectPage = true;
    }

    public function clearSelection(): void
    {
        $this->selected = [];
        $this->selectPage = false;
        $this->selectAllMatching = false;
    }

    public function clearFilters(): void
    {
        $this->reset([
            'search',
            'status',
            'category',
            'country',
            'countryGroup',
            'locale',
            'finishFrom',
            'finishTo',
            'selected',
            'selectPage',
            'selectAllMatching',
        ]);

        $this->resetPage();
    }


    public function sendSelected(): void
    {
        $merchant = $this->currentMerchant();

        if (! $merchant) {
            return;
        }

        if (! $merchant->market_url) {
            session()->flash(
                'markets-error',
                'Market URL is not configured for your merchant account.'
            );

            return;
        }

        /*
         * MODE 1:
         * Select all markets matching current filters.
         *
         * В браузер тысячи ID не передаются.
         */
        if ($this->selectAllMatching) {

            $query = $this->markets()
                ->where('status', BetStatus::PUBLISHED->value)
                ->whereDoesntHave('merchantBets', function ($query) use ($merchant) {
                    $query
                        ->where('merchant_id', $merchant->id)
                        ->whereNotNull('delivered_at');
                });

            $queued = 0;
            $alreadyDelivered = 0;

            $query->select('bets.id')
                ->chunkById(500, function ($bets) use (
                    $merchant,
                    &$queued,
                    &$alreadyDelivered
                ) {

                    foreach ($bets as $bet) {

                        $merchantBet = $merchant->merchantBets()
                            ->firstOrCreate(
                                [
                                    'bet_id' => $bet->id,
                                ],
                                [
                                    'status' => 'queued',
                                    'delivery_source' => 'manual',
                                    'billing_mode' => null,
                                    'unit_price' => 0,
                                    'charged_amount' => 0,
                                ]
                            );

                        /*
                         * Уже успешно доставлен —
                         * повторно initial market не отправляем.
                         */
                        if ($merchantBet->delivered_at !== null) {
                            $alreadyDelivered++;

                            continue;
                        }

                        $merchantBet->update([
                            'status' => 'queued',
                        ]);

                        SendMarketToMerchant::dispatch(
                            $merchantBet->id
                        );

                        $queued++;
                    }
                }, 'bets.id', 'id');

            $this->clearSelection();

            $message =
                number_format($queued)
                . ' market'
                . ($queued === 1 ? '' : 's')
                . ' queued for API delivery.';

            if ($alreadyDelivered > 0) {
                $message .= ' '
                    . number_format($alreadyDelivered)
                    . ' already delivered and skipped.';
            }

            session()->flash(
                'markets-success',
                $message
            );

            return;
        }


        /*
         * MODE 2:
         * Обычный выбор checkbox.
         */
        if (empty($this->selected)) {
            return;
        }

        /*
         * Даже если ID подменить вручную,
         * отправляем ТОЛЬКО PUBLISHED.
         */
        $betIds = Bet::query()
            ->whereIn('id', $this->selected)
            ->where(
                'status',
                BetStatus::PUBLISHED->value
            )
            ->pluck('id');

        $queued = 0;
        $alreadyDelivered = 0;

        foreach ($betIds as $betId) {

            $merchantBet = $merchant->merchantBets()
                ->firstOrCreate(
                    [
                        'bet_id' => $betId,
                    ],
                    [
                        'status' => 'queued',
                        'delivery_source' => 'manual',
                        'billing_mode' => null,
                        'unit_price' => 0,
                        'charged_amount' => 0,
                    ]
                );

            if ($merchantBet->delivered_at !== null) {
                $alreadyDelivered++;

                continue;
            }

            $merchantBet->update([
                'status' => 'queued',
            ]);

            SendMarketToMerchant::dispatch(
                $merchantBet->id
            );

            $queued++;
        }

        $this->clearSelection();

        $message =
            number_format($queued)
            . ' market'
            . ($queued === 1 ? '' : 's')
            . ' queued for API delivery.';

        if ($alreadyDelivered > 0) {
            $message .= ' '
                . number_format($alreadyDelivered)
                . ' already delivered and skipped.';
        }

        session()->flash(
            'markets-success',
            $message
        );
    }



    public function markets()
    {
        $merchant = $this->currentMerchant();

        return Bet::query()

            ->whereIn('status', [
                BetStatus::PUBLISHED->value,
                BetStatus::RESOLVING->value,
                BetStatus::RESOLVED->value,
            ])

            ->with([
                'translations',
                'categories',
                'countries',
                'countryGroups',

                'merchantBets' => function ($query) use ($merchant) {
                    if ($merchant) {
                        $query->where('merchant_id', $merchant->id);
                    } else {
                        $query->whereRaw('1 = 0');
                    }
                },
            ])

            ->when(
                $this->search !== '',
                function ($query) {
                    $search = trim($this->search);

                    $query->where(function ($query) use ($search) {

                        $query->where('id', $search)

                            ->orWhereHas(
                                'translations',
                                fn ($translationQuery) =>
                                $translationQuery->where(
                                    'title',
                                    'like',
                                    '%' . $search . '%'
                                )
                            );
                    });
                }
            )

            ->when(
                $this->status !== '',
                fn ($query) =>
                $query->where('status', $this->status)
            )

            ->when(
                $this->category !== '',
                fn ($query) =>
                $query->whereHas(
                    'categories',
                    fn ($categoryQuery) =>
                    $categoryQuery->where(
                        'categories.id',
                        $this->category
                    )
                )
            )

            ->when(
                $this->country !== '',
                fn ($query) =>
                $query->whereHas(
                    'countries',
                    fn ($countryQuery) =>
                    $countryQuery->where(
                        'countries.id',
                        $this->country
                    )
                )
            )

            ->when(
                $this->countryGroup !== '',
                fn ($query) =>
                $query->whereHas(
                    'countryGroups',
                    fn ($groupQuery) =>
                    $groupQuery->where(
                        'country_groups.id',
                        $this->countryGroup
                    )
                )
            )

            ->when(
                $this->locale !== '',
                fn ($query) =>
                $query->whereHas(
                    'translations',
                    fn ($translationQuery) =>
                    $translationQuery->where(
                        'locale',
                        $this->locale
                    )
                )
            )

            ->when(
                $this->finishFrom !== '',
                fn ($query) =>
                $query->whereDate(
                    'finish_at',
                    '>=',
                    $this->finishFrom
                )
            )

            ->when(
                $this->finishTo !== '',
                fn ($query) =>
                $query->whereDate(
                    'finish_at',
                    '<=',
                    $this->finishTo
                )
            )

            ->orderByRaw('finish_at IS NULL')
            ->orderBy('finish_at')
            ->orderByDesc('id');
    }

    public function with(): array
    {
        return [
            'markets' => $this->markets()->paginate(20),

            'categories' => Category::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),

            'countries' => Country::query()
                ->orderBy('name')
                ->get(),

            'countryGroups' => CountryGroup::query()
                ->orderBy('name')
                ->get(),

            'locales' => \App\Models\BetTranslation::query()
                ->select('locale')
                ->distinct()
                ->orderBy('locale')
                ->pluck('locale'),
        ];
    }
};

?>

<div class="space-y-6">

    {{-- HEADER --}}
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">

        <div>
            <h1 class="text-2xl font-semibold wr-text">
                Markets
            </h1>

            <p class="mt-1 text-sm wr-muted">
                Browse prediction markets available for delivery to your platform.
            </p>
        </div>

        <div class="flex items-center gap-3">

            @if(count($selected) > 0 || $selectAllMatching)

                @php
                    $merchant = $this->currentMerchant();

                    $selectedAvailableCount = $merchant
                        ? \App\Models\Bet::query()
                            ->whereIn('id', $selected)
                            ->where('status', \App\Enums\BetStatus::PUBLISHED->value)
                            ->whereDoesntHave('merchantBets', function ($query) use ($merchant) {
                                $query
                                    ->where('merchant_id', $merchant->id)
                                    ->whereNotNull('delivered_at');
                            })
                            ->count()
                        : 0;

                    $matchingPublishedCount = $merchant
                        ? $this->markets()
                            ->where('status', \App\Enums\BetStatus::PUBLISHED->value)
                            ->whereDoesntHave('merchantBets', function ($query) use ($merchant) {
                                $query
                                    ->where('merchant_id', $merchant->id)
                                    ->whereNotNull('delivered_at');
                            })
                            ->count()
                        : 0;
                @endphp

                <div
                    class="rounded-lg border px-3 py-2 text-sm wr-text"
                    style="
            border-color: var(--wr-border);
            background: var(--wr-panel);
        "
                >
                    @if($selectAllMatching)
                        All {{ number_format($matchingPublishedCount) }} matching markets selected
                    @else
                        {{ $selectedAvailableCount }} selected
                    @endif
                </div>

                <button
                    type="button"
                    wire:click="sendSelected"
                    wire:confirm="Send selected markets to your API?"
                    wire:loading.attr="disabled"
                    wire:target="sendSelected"
                    class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-500 disabled:opacity-50"
                >
        <span wire:loading.remove wire:target="sendSelected">
            Send selected via API
        </span>

                    <span wire:loading wire:target="sendSelected">
            Queuing...
        </span>
                </button>

                <button
                    type="button"
                    wire:click="clearSelection"
                    class="rounded-lg border px-3 py-2 text-sm font-medium wr-text"
                    style="border-color: var(--wr-border);"
                >
                    Clear selection
                </button>

            @endif

        </div>

    </div>

    @if(session()->has('markets-error'))

        <div
            class="rounded-xl border px-4 py-3 text-sm font-medium text-red-500"
            style="
            background: var(--wr-panel);
            border-color: var(--wr-border);
        "
        >
            {{ session('markets-error') }}
        </div>

    @endif

    @if(session()->has('markets-success'))

        <div
            class="rounded-xl border px-4 py-3 text-sm font-medium text-emerald-500"
            style="
            background: var(--wr-panel);
            border-color: var(--wr-border);
        "
        >
            {{ session('markets-success') }}
        </div>

    @endif


    {{-- FILTERS --}}
    <div class="wr-panel rounded-xl border p-5">

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">

            {{-- Search --}}
            <div class="xl:col-span-2">

                <label class="mb-1.5 block text-xs font-medium wr-muted">
                    Search
                </label>

                <input
                    type="text"
                    wire:model.live.debounce.400ms="search"
                    placeholder="Market title or ID..."
                    class="w-full rounded-lg border px-3 py-2.5 text-sm outline-none wr-text"
                    style="
                        background: var(--wr-input);
                        border-color: var(--wr-border);
                    "
                >

            </div>


            {{-- Status --}}
            <div>

                <label class="mb-1.5 block text-xs font-medium wr-muted">
                    Status
                </label>

                <select
                    wire:model.live="status"
                    class="w-full rounded-lg border px-3 py-2.5 text-sm outline-none wr-text"
                    style="
                        background: var(--wr-input);
                        border-color: var(--wr-border);
                    "
                >
                    <option value="">All statuses</option>
                    <option value="published">Published</option>
                    <option value="resolving">Resolving</option>
                    <option value="resolved">Resolved</option>
                </select>

            </div>


            {{-- Language --}}
            <div>

                <label class="mb-1.5 block text-xs font-medium wr-muted">
                    Language
                </label>

                <select
                    wire:model.live="locale"
                    class="w-full rounded-lg border px-3 py-2.5 text-sm outline-none wr-text"
                    style="
                        background: var(--wr-input);
                        border-color: var(--wr-border);
                    "
                >
                    <option value="">All languages</option>

                    @foreach($locales as $item)
                        <option value="{{ $item }}">
                            {{ strtoupper($item) }}
                        </option>
                    @endforeach

                </select>

            </div>


            {{-- Category --}}
            <div>

                <label class="mb-1.5 block text-xs font-medium wr-muted">
                    Category
                </label>

                <select
                    wire:model.live="category"
                    class="w-full rounded-lg border px-3 py-2.5 text-sm outline-none wr-text"
                    style="
                        background: var(--wr-input);
                        border-color: var(--wr-border);
                    "
                >
                    <option value="">All categories</option>

                    @foreach($categories as $item)
                        <option value="{{ $item->id }}">
                            {{ $item->name }}
                        </option>
                    @endforeach

                </select>

            </div>


            {{-- Country --}}
            <div>

                <label class="mb-1.5 block text-xs font-medium wr-muted">
                    Country
                </label>

                <select
                    wire:model.live="country"
                    class="w-full rounded-lg border px-3 py-2.5 text-sm outline-none wr-text"
                    style="
                        background: var(--wr-input);
                        border-color: var(--wr-border);
                    "
                >
                    <option value="">All countries</option>

                    @foreach($countries as $item)
                        <option value="{{ $item->id }}">
                            {{ $item->name }}
                        </option>
                    @endforeach

                </select>

            </div>


            {{-- Country Group --}}
            <div>

                <label class="mb-1.5 block text-xs font-medium wr-muted">
                    Country Group
                </label>

                <select
                    wire:model.live="countryGroup"
                    class="w-full rounded-lg border px-3 py-2.5 text-sm outline-none wr-text"
                    style="
                        background: var(--wr-input);
                        border-color: var(--wr-border);
                    "
                >
                    <option value="">All country groups</option>

                    @foreach($countryGroups as $item)
                        <option value="{{ $item->id }}">
                            {{ $item->name }}
                        </option>
                    @endforeach

                </select>

            </div>


            {{-- Finish From --}}
            <div>

                <label class="mb-1.5 block text-xs font-medium wr-muted">
                    Finish from
                </label>

                <input
                    type="date"
                    wire:model.live="finishFrom"
                    class="w-full rounded-lg border px-3 py-2.5 text-sm outline-none wr-text"
                    style="
                        background: var(--wr-input);
                        border-color: var(--wr-border);
                    "
                >

            </div>


            {{-- Finish To --}}
            <div>

                <label class="mb-1.5 block text-xs font-medium wr-muted">
                    Finish to
                </label>

                <input
                    type="date"
                    wire:model.live="finishTo"
                    class="w-full rounded-lg border px-3 py-2.5 text-sm outline-none wr-text"
                    style="
                        background: var(--wr-input);
                        border-color: var(--wr-border);
                    "
                >

            </div>

        </div>


        <div class="mt-4 flex justify-end">

            <button
                type="button"
                wire:click="clearFilters"
                class="rounded-lg border px-4 py-2 text-sm font-medium wr-text"
                style="border-color: var(--wr-border);"
            >
                Clear filters
            </button>

        </div>

    </div>


    @if($selectPage && ! $selectAllMatching)

        @php
            $merchant = $this->currentMerchant();

            $selectedAvailableCount = $merchant
                ? \App\Models\Bet::query()
                    ->whereIn('id', $selected)
                    ->where(
                        'status',
                        \App\Enums\BetStatus::PUBLISHED->value
                    )
                    ->whereDoesntHave(
                        'merchantBets',
                        function ($query) use ($merchant) {
                            $query
                                ->where('merchant_id', $merchant->id)
                                ->whereNotNull('delivered_at');
                        }
                    )
                    ->count()
                : 0;

            $matchingPublishedCount = $merchant
                ? $this->markets()
                    ->where(
                        'status',
                        \App\Enums\BetStatus::PUBLISHED->value
                    )
                    ->whereDoesntHave(
                        'merchantBets',
                        function ($query) use ($merchant) {
                            $query
                                ->where('merchant_id', $merchant->id)
                                ->whereNotNull('delivered_at');
                        }
                    )
                    ->count()
                : 0;
        @endphp

        @if($matchingPublishedCount > $selectedAvailableCount)

            <div
                class="rounded-xl border px-4 py-3 text-sm wr-text"
                style="
            background: var(--wr-panel);
            border-color: var(--wr-border);
        "
            >
                <div class="flex flex-wrap items-center justify-between gap-3">

                    <div>
                        <strong>{{ $selectedAvailableCount }}</strong>
                        published markets selected on this page.

                        There are
                        <strong>{{ number_format($matchingPublishedCount) }}</strong>
                        published markets matching the current filters.
                    </div>

                    <button
                        type="button"
                        wire:click="selectAllMatchingMarkets"
                        class="font-semibold text-indigo-500 hover:text-indigo-400"
                    >
                        Select all {{ number_format($matchingPublishedCount) }}
                    </button>

                </div>
            </div>

        @endif

    @endif




    {{-- MARKETS TABLE --}}
    <div class="wr-panel overflow-hidden rounded-xl border">

        <div class="overflow-x-auto">

            <table class="min-w-full">

                <thead
                    style="background: var(--wr-panel-secondary);"
                >
                <tr>

                    <th class="w-12 px-4 py-3 text-left">

                        <input
                            type="checkbox"
                            wire:model.live="selectPage"
                            class="h-4 w-4 rounded"
                        >

                    </th>

                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider wr-muted">
                        Market
                    </th>

                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider wr-muted">
                        Category
                    </th>

                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider wr-muted">
                        Geography
                    </th>

                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider wr-muted">
                        Languages
                    </th>

                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider wr-muted">
                        Finish
                    </th>

                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider wr-muted">
                        Status
                    </th>

                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider wr-muted">
                        Delivery
                    </th>

                </tr>
                </thead>


                <tbody>

                @forelse($markets as $market)

                    @php
                        $translation =
                            $market->translations
                                ->firstWhere('locale', 'en')
                            ?? $market->translations->first();

                        $merchantBet = $market->merchantBets->first();

                        $statusValue = $market->status instanceof \App\Enums\BetStatus
                            ? $market->status->value
                            : $market->status;
                    @endphp

                    <tr
                        wire:key="market-{{ $market->id }}"
                        class="border-t"
                        style="border-color: var(--wr-border-soft);"
                    >

                        {{-- Checkbox --}}
                        <td class="px-4 py-4 align-top">

                            @if($statusValue !== 'published')

                                <div x-data class="inline-flex">
                                    <input
                                        type="checkbox"
                                        class="h-4 w-4 cursor-not-allowed rounded opacity-50"
                                        x-on:click.prevent="
                window.alert('This market cannot be sent via API. Only markets with Published status can be sent.')
            "
                                    >
                                </div>

                            @elseif($merchantBet && $merchantBet->delivered_at)

                                <div x-data class="inline-flex">
                                    <input
                                        type="checkbox"
                                        class="h-4 w-4 cursor-not-allowed rounded opacity-50"
                                        x-on:click.prevent="
                window.alert('This market has already been delivered to your API.')
            "
                                    >
                                </div>

                            @else

                                <input
                                    type="checkbox"
                                    value="{{ $market->id }}"

                                    x-data

                                    x-bind:checked="
        $wire.selectAllMatching ||
        $wire.selected.map(String).includes('{{ $market->id }}')
    "

                                    x-on:change="
        let ids = $wire.selected.map(String);
        let id = '{{ $market->id }}';

        if ($event.target.checked) {
            if (!ids.includes(id)) {
                ids.push(id);
            }
        } else {
            if ($wire.selectAllMatching) {
                $event.target.checked = true;
                return;
            }

            ids = ids.filter(item => item !== id);
        }

        $wire.selected = ids;
    "

                                    class="h-4 w-4 cursor-pointer rounded"
                                >

                            @endif

                        </td>


                        {{-- Market --}}
                        <td class="min-w-[320px] px-4 py-4 align-top">

                            <a
                                href="{{ route('merchant.markets.show', $market->id) }}"
                                class="group block"
                            >
                                <div class="font-medium wr-text transition group-hover:underline">
                                    {{ $translation?->title ?? 'Untitled market' }}
                                </div>

                                <div class="mt-1 text-xs wr-muted transition group-hover:underline">
                                    Market #{{ $market->id }}
                                </div>
                            </a>

                        </td>


                        {{-- Category --}}
                        <td class="px-4 py-4 align-top">

                            <div class="flex max-w-[220px] flex-wrap gap-1">

                                @forelse($market->categories as $item)

                                    <span
                                        class="rounded-md border px-2 py-1 text-xs wr-text"
                                        style="border-color: var(--wr-border);"
                                    >
                                            {{ $item->name }}
                                        </span>

                                @empty

                                    <span class="text-sm wr-muted">
                                            —
                                        </span>

                                @endforelse

                            </div>

                        </td>


                        {{-- Geography --}}
                        <td class="px-4 py-4 align-top">

                            <div class="max-w-[220px] text-sm wr-text">

                                @if($market->countries->isNotEmpty())

                                    {{ $market->countries
                                        ->pluck('name')
                                        ->join(', ') }}

                                @elseif($market->countryGroups->isNotEmpty())

                                    {{ $market->countryGroups
                                        ->pluck('name')
                                        ->join(', ') }}

                                @else

                                    <span class="wr-muted">
                                            Global
                                        </span>

                                @endif

                            </div>

                        </td>


                        {{-- Languages --}}
                        <td class="px-4 py-4 align-top">

                            <div class="flex max-w-[180px] flex-wrap gap-1">

                                @foreach(
                                    $market->translations
                                        ->pluck('locale')
                                        ->unique()
                                    as $marketLocale
                                )

                                    <span
                                        class="rounded-md border px-2 py-1 text-xs uppercase wr-text"
                                        style="border-color: var(--wr-border);"
                                    >
                                            {{ $marketLocale }}
                                        </span>

                                @endforeach

                            </div>

                        </td>


                        {{-- Finish --}}
                        <td class="whitespace-nowrap px-4 py-4 align-top text-sm wr-text">

                            @if($market->finish_at)

                                {{ $market->finish_at->format('d M Y') }}

                                <div class="mt-1 text-xs wr-muted">
                                    {{ $market->finish_at->format('H:i') }}
                                </div>

                            @else

                                <span class="wr-muted">
                                        —
                                    </span>

                            @endif

                        </td>


                        {{-- Status --}}
                        <td class="px-4 py-4 align-top">

                            @if($statusValue === 'published')

                                <span class="inline-flex rounded-full bg-emerald-500/10 px-2.5 py-1 text-xs font-semibold text-emerald-500">
                                        Published
                                    </span>

                            @elseif($statusValue === 'resolving')

                                <span class="inline-flex rounded-full bg-amber-500/10 px-2.5 py-1 text-xs font-semibold text-amber-500">
                                        Resolving
                                    </span>

                            @elseif($statusValue === 'resolved')

                                <span class="inline-flex rounded-full bg-blue-500/10 px-2.5 py-1 text-xs font-semibold text-blue-500">
                                        Resolved
                                    </span>

                            @else

                                <span class="wr-muted">
                                        {{ ucfirst($statusValue) }}
                                    </span>

                            @endif

                        </td>


                        {{-- Delivery --}}
                        <td class="px-4 py-4 align-top">

                            @php
                                $deliveryStatus = $merchantBet
                                    ? (
                                        $merchantBet->status instanceof \BackedEnum
                                            ? $merchantBet->status->value
                                            : $merchantBet->status
                                    )
                                    : null;
                            @endphp

                            @if(!$merchantBet)

                                <span class="inline-flex rounded-full bg-gray-500/10 px-2.5 py-1 text-xs font-semibold wr-muted">
                                    Not delivered
                                </span>

                            @elseif($deliveryStatus === 'delivered')

                                <span class="inline-flex rounded-full bg-emerald-500/10 px-2.5 py-1 text-xs font-semibold text-emerald-500">
                                    Delivered
                                </span>

                                @if($merchantBet->delivered_at)
                                    <div class="mt-1 text-xs wr-muted">
                                        {{ $merchantBet->delivered_at->format('d M Y H:i') }}
                                    </div>
                                @endif

                            @elseif($deliveryStatus === 'queued')

                                <span class="inline-flex rounded-full bg-blue-500/10 px-2.5 py-1 text-xs font-semibold text-blue-500">
                                    Queued
                                </span>

                            @elseif($deliveryStatus === 'callback_failed')

                                <span class="inline-flex rounded-full bg-red-500/10 px-2.5 py-1 text-xs font-semibold text-red-500">
                                    Failed
                                </span>

                            @else

                                <span class="inline-flex rounded-full bg-amber-500/10 px-2.5 py-1 text-xs font-semibold text-amber-500">
                                    {{ ucfirst(str_replace('_', ' ', $deliveryStatus)) }}
                                </span>

                            @endif

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td
                            colspan="8"
                            class="px-6 py-16 text-center"
                        >

                            <div class="text-base font-medium wr-text">
                                No markets found
                            </div>

                            <div class="mt-1 text-sm wr-muted">
                                Try changing your filters.
                            </div>

                        </td>

                    </tr>

                @endforelse

                </tbody>

            </table>

        </div>


        {{-- PAGINATION --}}
        @if($markets->hasPages())

            <div
                class="border-t px-5 py-4"
                style="border-color: var(--wr-border);"
            >
                {{ $markets->links() }}
            </div>

        @endif

    </div>


    {{-- LOADING --}}
    <div
        wire:loading.flex
        class="fixed inset-0 z-50 items-center justify-center bg-black/20 backdrop-blur-[1px]"
    >
        <div
            class="rounded-xl border px-5 py-3 text-sm font-medium wr-text shadow-lg"
            style="
                background: var(--wr-panel);
                border-color: var(--wr-border);
            "
        >
            Loading markets...
        </div>
    </div>

</div>
