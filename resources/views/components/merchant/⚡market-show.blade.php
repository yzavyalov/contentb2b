<?php
use App\Enums\BetStatus;
use App\Jobs\SendMarketToMerchant;
use App\Models\Bet;
use App\Services\CurrentMerchant;
use Livewire\Component;

new class extends Component
{
    public int $betId;

    public string $locale = '';

    public function mount(int $betId): void
    {
        $this->betId = $betId;

        $bet = $this->loadBet();

        /*
         * По умолчанию показываем исходный язык рынка.
         * Если такого перевода почему-то нет — первый доступный.
         */
        $availableLocales = $bet->translations
            ->pluck('locale')
            ->filter()
            ->values();

        if (
            $bet->source_locale
            && $availableLocales->contains($bet->source_locale)
        ) {
            $this->locale = $bet->source_locale;
        } else {
            $this->locale = (string) ($availableLocales->first() ?? '');
        }
    }


    private function currentMerchant()
    {
        return app(CurrentMerchant::class)->get();
    }


    private function loadBet(): Bet
    {
        $merchant = $this->currentMerchant();

        $bet = Bet::query()
            ->where('id', $this->betId)
            ->whereIn('status', [
                BetStatus::PUBLISHED->value,
                BetStatus::RESOLVING->value,
                BetStatus::RESOLVED->value,
            ])
            ->with([
                'translations',

                'answers' => function ($query) {
                    $query
                        ->with('translations')
                        ->orderBy('sort_order')
                        ->orderBy('id');
                },

                'winningAnswer.translations',

                'sources' => function ($query) {
                    $query
                        ->orderBy('sort_order')
                        ->orderBy('id');
                },

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
            ->firstOrFail();

        return $bet;
    }


    public function sendMarket(): void
    {
        $merchant = $this->currentMerchant();

        if (! $merchant) {
            session()->flash(
                'market-error',
                'Please select a merchant account first.'
            );

            return;
        }

        if (! $merchant->market_url) {
            session()->flash(
                'market-error',
                'Market URL is not configured for your merchant account.'
            );

            return;
        }

        /*
         * Initial delivery разрешён только для PUBLISHED.
         */
        $bet = Bet::query()
            ->where('id', $this->betId)
            ->where('status', BetStatus::PUBLISHED->value)
            ->first();

        if (! $bet) {
            session()->flash(
                'market-error',
                'Only published markets can be sent.'
            );

            return;
        }

        /*
         * Не создаём второй MerchantBet для того же market.
         */
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
         * Уже успешно доставлен.
         */
        if ($merchantBet->delivered_at !== null) {
            session()->flash(
                'market-error',
                'This market has already been delivered to your platform.'
            );

            return;
        }

        $merchantBet->update([
            'status' => 'queued',
            'delivery_source' => 'manual',
        ]);

        SendMarketToMerchant::dispatch(
            $merchantBet->id
        );

        session()->flash(
            'market-success',
            'Market queued for API delivery.'
        );
    }

    public function with(): array
    {
        $bet = $this->loadBet();

        /*
         * Не позволяем вручную подставить locale,
         * которого у данного market нет.
         */
        $availableLocales = $bet->translations
            ->pluck('locale')
            ->filter()
            ->values();

        if (
            $this->locale === ''
            || ! $availableLocales->contains($this->locale)
        ) {
            $this->locale = (string) (
            $availableLocales->contains($bet->source_locale)
                ? $bet->source_locale
                : ($availableLocales->first() ?? '')
            );
        }

        $translation = $bet->translations
            ->firstWhere('locale', $this->locale);

        /*
         * Если перевод market отсутствует, используем исходный.
         */
        if (! $translation && $bet->source_locale) {
            $translation = $bet->translations
                ->firstWhere('locale', $bet->source_locale);
        }

        $merchantBet = $bet->merchantBets->first();

        return [
            'bet' => $bet,
            'translation' => $translation,
            'availableLocales' => $availableLocales,
            'merchantBet' => $merchantBet,
        ];
    }
};

?>

<div class="space-y-6">

    {{-- ========================================================= --}}
    {{-- BACK / HEADER --}}
    {{-- ========================================================= --}}

    @if(session('market-success'))
        <div
            class="rounded-xl border px-4 py-3 text-sm font-medium wr-text"
            style="border-color: var(--wr-border);"
        >
            {{ session('market-success') }}
        </div>
    @endif

    @if(session('market-error'))
        <div
            class="rounded-xl border px-4 py-3 text-sm font-medium wr-text"
            style="border-color: var(--wr-border);"
        >
            {{ session('market-error') }}
        </div>
    @endif



    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">

        <div>

            <a
                href="{{ route('merchant.markets') }}"
                class="inline-flex items-center gap-2 text-sm font-medium wr-muted hover:underline"
            >
                <span aria-hidden="true">←</span>
                Back to Markets
            </a>

            <div class="mt-4 flex flex-wrap items-center gap-3">

                <h1 class="text-2xl font-semibold wr-text">
                    Market #{{ $bet->id }}
                </h1>

                @php
                    $statusValue = $bet->status instanceof \BackedEnum
                        ? $bet->status->value
                        : $bet->status;

                    $statusLabel = ucfirst(
                        str_replace('_', ' ', $statusValue)
                    );
                @endphp

                <span
                    class="inline-flex rounded-full border px-3 py-1 text-xs font-semibold wr-text"
                    style="border-color: var(--wr-border);"
                >
                    {{ $statusLabel }}
                </span>

            </div>

            <p class="mt-2 text-sm wr-muted">
                Read-only market information available to your merchant account.
            </p>

        </div>


        {{-- LANGUAGE --}}

        @if($availableLocales->count() > 1)

            <div class="min-w-[180px]">

                <label class="mb-1 block text-xs font-medium wr-muted">
                    Language
                </label>

                <select
                    wire:model.live="locale"
                    class="w-full rounded-xl border px-3 py-2 text-sm wr-text"
                    style="
                        border-color: var(--wr-border);
                        background: var(--wr-input);
                    "
                >
                    @foreach($availableLocales as $availableLocale)

                        <option value="{{ $availableLocale }}">
                            {{ strtoupper($availableLocale) }}

                            @if($availableLocale === $bet->source_locale)
                                — Source
                            @endif
                        </option>

                    @endforeach
                </select>

            </div>

        @elseif($availableLocales->count() === 1)

            <div
                class="rounded-xl border px-4 py-2 text-sm wr-text"
                style="border-color: var(--wr-border);"
            >
                Language:
                <span class="font-semibold">
                    {{ strtoupper($availableLocales->first()) }}
                </span>
            </div>

        @endif

    </div>



    {{-- ========================================================= --}}
    {{-- MAIN MARKET CONTENT --}}
    {{-- ========================================================= --}}

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_320px]">

        <div class="space-y-6">


            {{-- TITLE + DESCRIPTION --}}

            <section
                class="rounded-2xl border p-5 sm:p-6"
                style="border-color: var(--wr-border);"
            >

                <div class="text-xs font-semibold uppercase tracking-wider wr-muted">
                    Market
                </div>

                <h2 class="mt-2 text-xl font-semibold leading-snug wr-text">
                    {{ $translation?->title ?? 'Untitled market' }}
                </h2>


                @if($translation?->description)

                    <div
                        class="mt-5 whitespace-pre-line text-sm leading-7 wr-text"
                    >{{ $translation->description }}</div>

                @else

                    <p class="mt-4 text-sm italic wr-muted">
                        No description is available for this language.
                    </p>

                @endif

            </section>



            {{-- ================================================= --}}
            {{-- ANSWERS --}}
            {{-- ================================================= --}}

            <section
                class="rounded-2xl border p-5 sm:p-6"
                style="border-color: var(--wr-border);"
            >

                <div class="flex items-center justify-between gap-4">

                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wider wr-muted">
                            Outcomes
                        </div>

                        <h2 class="mt-1 text-lg font-semibold wr-text">
                            Answer options
                        </h2>

                    </div>

                    <div class="text-sm wr-muted">
                        {{ $bet->answers->count() }}
                        {{ $bet->answers->count() === 1 ? 'option' : 'options' }}
                    </div>

                </div>


                <div class="mt-5 space-y-3">

                    @forelse($bet->answers as $answer)

                        @php

                            /*
                             * Сначала пытаемся получить ответ
                             * на выбранном языке.
                             */
                            $answerTranslation = $answer->translations
                                ->firstWhere('locale', $locale);

                            /*
                             * Fallback на исходный язык.
                             */
                            if (! $answerTranslation && $bet->source_locale) {
                                $answerTranslation = $answer->translations
                                    ->firstWhere(
                                        'locale',
                                        $bet->source_locale
                                    );
                            }

                            /*
                             * Последний fallback —
                             * любой имеющийся перевод.
                             */
                            if (! $answerTranslation) {
                                $answerTranslation =
                                    $answer->translations->first();
                            }

                            $isWinner =
                                $bet->winning_answer_id !== null
                                && (int) $bet->winning_answer_id
                                    === (int) $answer->id;

                        @endphp


                        <div
                            class="flex items-start gap-4 rounded-xl border p-4"
                            style="border-color: var(--wr-border);"
                        >

                            <div
                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border text-xs font-semibold wr-muted"
                                style="border-color: var(--wr-border);"
                            >
                                {{ $loop->iteration }}
                            </div>


                            <div class="min-w-0 flex-1">

                                <div class="font-medium wr-text">
                                    {{ $answerTranslation?->title ?? 'Untitled answer' }}
                                </div>

                                @if(
                                    $answerTranslation
                                    && $answerTranslation->locale !== $locale
                                )

                                    <div class="mt-1 text-xs wr-muted">
                                        Showing
                                        {{ strtoupper($answerTranslation->locale) }}
                                        fallback
                                    </div>

                                @endif

                            </div>


                            @if($isWinner)

                                <span
                                    class="shrink-0 rounded-full border px-3 py-1 text-xs font-semibold wr-text"
                                    style="border-color: var(--wr-border);"
                                >
                                    Winner
                                </span>

                            @endif

                        </div>

                    @empty

                        <div
                            class="rounded-xl border border-dashed p-5 text-sm wr-muted"
                            style="border-color: var(--wr-border);"
                        >
                            No answer options are available.
                        </div>

                    @endforelse

                </div>

            </section>



            {{-- ================================================= --}}
            {{-- SOURCES --}}
            {{-- ================================================= --}}

            <section
                class="rounded-2xl border p-5 sm:p-6"
                style="border-color: var(--wr-border);"
            >

                <div class="text-xs font-semibold uppercase tracking-wider wr-muted">
                    Resolution
                </div>

                <h2 class="mt-1 text-lg font-semibold wr-text">
                    Sources
                </h2>


                <div class="mt-5 space-y-3">

                    @forelse($bet->sources as $source)

                        <a
                            href="{{ $source->url }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="flex items-start gap-3 rounded-xl border p-4 transition hover:opacity-80"
                            style="border-color: var(--wr-border);"
                        >

                            <div class="mt-0.5 shrink-0 wr-muted">
                                ↗
                            </div>

                            <div class="min-w-0">

                                <div class="text-sm font-medium wr-text">
                                    Source {{ $loop->iteration }}
                                </div>

                                <div class="mt-1 break-all text-xs wr-muted">
                                    {{ $source->url }}
                                </div>

                            </div>

                        </a>

                    @empty

                        <div
                            class="rounded-xl border border-dashed p-5 text-sm wr-muted"
                            style="border-color: var(--wr-border);"
                        >
                            No resolution sources have been specified.
                        </div>

                    @endforelse

                </div>

            </section>

        </div>



        {{-- ===================================================== --}}
        {{-- RIGHT SIDEBAR --}}
        {{-- ===================================================== --}}

        <div class="space-y-6">


            {{-- MARKET INFORMATION --}}

            <section
                class="rounded-2xl border p-5"
                style="border-color: var(--wr-border);"
            >

                <h2 class="font-semibold wr-text">
                    Market information
                </h2>


                <dl class="mt-5 space-y-4">

                    <div>
                        <dt class="text-xs wr-muted">
                            Market ID
                        </dt>

                        <dd class="mt-1 text-sm font-medium wr-text">
                            #{{ $bet->id }}
                        </dd>
                    </div>


                    <div>
                        <dt class="text-xs wr-muted">
                            Status
                        </dt>

                        <dd class="mt-1 text-sm font-medium wr-text">
                            {{ $statusLabel }}
                        </dd>
                    </div>


                    <div>
                        <dt class="text-xs wr-muted">
                            Source language
                        </dt>

                        <dd class="mt-1 text-sm font-medium wr-text">
                            {{ strtoupper($bet->source_locale ?? '—') }}
                        </dd>
                    </div>


                    <div>
                        <dt class="text-xs wr-muted">
                            Finish date
                        </dt>

                        <dd class="mt-1 text-sm font-medium wr-text">
                            {{ $bet->finish_at?->format('Y-m-d H:i') ?? '—' }}
                        </dd>
                    </div>


                    @if($bet->published_at)

                        <div>
                            <dt class="text-xs wr-muted">
                                Published
                            </dt>

                            <dd class="mt-1 text-sm font-medium wr-text">
                                {{ $bet->published_at->format('Y-m-d H:i') }}
                            </dd>
                        </div>

                    @endif


                    @if($bet->resolved_at)

                        <div>
                            <dt class="text-xs wr-muted">
                                Resolved
                            </dt>

                            <dd class="mt-1 text-sm font-medium wr-text">
                                {{ $bet->resolved_at->format('Y-m-d H:i') }}
                            </dd>
                        </div>

                    @endif

                </dl>

            </section>



            {{-- ================================================= --}}
            {{-- DELIVERY --}}
            {{-- ================================================= --}}

            <section
                class="rounded-2xl border p-5"
                style="border-color: var(--wr-border);"
            >

                <h2 class="font-semibold wr-text">
                    Delivery
                </h2>


                {{-- Normalize MerchantBet enum values --}}
                @php
                    $merchantBetStatus = null;
                    $merchantBetDeliverySource = null;

                    if ($merchantBet) {
                        $merchantBetStatus = $merchantBet->status instanceof \BackedEnum
                            ? $merchantBet->status->value
                            : $merchantBet->status;

                        $merchantBetDeliverySource = $merchantBet->delivery_source instanceof \BackedEnum
                            ? $merchantBet->delivery_source->value
                            : $merchantBet->delivery_source;
                    }

                    $canSend =
                        $statusValue === \App\Enums\BetStatus::PUBLISHED->value
                        && (
                            ! $merchantBet
                            || $merchantBet->delivered_at === null
                        );
                @endphp


                {{-- Existing delivery information --}}
                @if($merchantBet)

                    <dl class="mt-5 space-y-4">

                        {{-- Status --}}
                        <div>
                            <dt class="text-xs wr-muted">
                                Status
                            </dt>

                            <dd class="mt-1 text-sm font-medium wr-text">
                                {{ $merchantBetStatus
                                    ? ucfirst(
                                        str_replace(
                                            '_',
                                            ' ',
                                            $merchantBetStatus
                                        )
                                    )
                                    : '—'
                                }}
                            </dd>
                        </div>


                        {{-- Delivery source --}}
                        <div>
                            <dt class="text-xs wr-muted">
                                Delivery source
                            </dt>

                            <dd class="mt-1 text-sm font-medium wr-text">
                                {{ $merchantBetDeliverySource
                                    ? ucfirst(
                                        str_replace(
                                            '_',
                                            ' ',
                                            $merchantBetDeliverySource
                                        )
                                    )
                                    : '—'
                                }}
                            </dd>
                        </div>


                        {{-- Delivered at --}}
                        <div>
                            <dt class="text-xs wr-muted">
                                Delivered
                            </dt>

                            <dd class="mt-1 text-sm font-medium wr-text">
                                {{ $merchantBet->delivered_at
                                    ? $merchantBet->delivered_at->format('Y-m-d H:i')
                                    : 'Not delivered'
                                }}
                            </dd>
                        </div>

                    </dl>

                @else

                    <p class="mt-3 text-sm leading-6 wr-muted">
                        This market has not been delivered to the selected merchant.
                    </p>

                @endif


                {{-- Send market --}}
                @if($canSend)

                    <button
                        type="button"
                        wire:click="sendMarket"
                        wire:loading.attr="disabled"
                        wire:target="sendMarket"
                        class="mt-5 inline-flex w-full items-center justify-center
                   rounded-xl bg-lime-500 px-4 py-3
                   text-sm font-semibold text-black
                   transition hover:bg-lime-400
                   disabled:cursor-not-allowed
                   disabled:opacity-50"
                    >

            <span
                wire:loading.remove
                wire:target="sendMarket"
            >
                Send to my platform
            </span>

                        <span
                            wire:loading
                            wire:target="sendMarket"
                        >
                Sending...
            </span>

                    </button>

                    <p class="mt-2 text-center text-xs wr-muted">
                        The market will be sent to your configured Market URL.
                    </p>


                    {{-- Already delivered --}}
                @elseif($merchantBet?->delivered_at)

                    <div
                        class="mt-5 rounded-xl border px-4 py-3
                   text-center text-sm font-medium wr-text"
                        style="border-color: var(--wr-border);"
                    >
                        ✓ Market delivered
                    </div>


                    {{-- Market cannot be initially delivered anymore --}}
                @elseif(
                    $statusValue !== \App\Enums\BetStatus::PUBLISHED->value
                )

                    <div
                        class="mt-5 rounded-xl border px-4 py-3
                   text-center text-sm wr-muted"
                        style="border-color: var(--wr-border);"
                    >
                        Initial delivery is available only for published markets.
                    </div>

                @endif

            </section>


            {{-- ================================================= --}}
            {{-- CATEGORIES --}}
            {{-- ================================================= --}}

            @if($bet->categories->isNotEmpty())

                <section
                    class="rounded-2xl border p-5"
                    style="border-color: var(--wr-border);"
                >

                    <h2 class="font-semibold wr-text">
                        Categories
                    </h2>

                    <div class="mt-4 flex flex-wrap gap-2">

                        @foreach($bet->categories as $category)

                            <span
                                class="rounded-full border px-3 py-1 text-xs wr-text"
                                style="border-color: var(--wr-border);"
                            >
                                {{ $category->name }}
                            </span>

                        @endforeach

                    </div>

                </section>

            @endif



            {{-- ================================================= --}}
            {{-- GEOGRAPHY --}}
            {{-- ================================================= --}}

            @if(
                $bet->countries->isNotEmpty()
                || $bet->countryGroups->isNotEmpty()
            )

                <section
                    class="rounded-2xl border p-5"
                    style="border-color: var(--wr-border);"
                >

                    <h2 class="font-semibold wr-text">
                        Geography
                    </h2>


                    @if($bet->countryGroups->isNotEmpty())

                        <div class="mt-4">

                            <div class="text-xs wr-muted">
                                Country groups
                            </div>

                            <div class="mt-2 flex flex-wrap gap-2">

                                @foreach($bet->countryGroups as $group)

                                    <span
                                        class="rounded-full border px-3 py-1 text-xs wr-text"
                                        style="border-color: var(--wr-border);"
                                    >
                                        {{ $group->name }}
                                    </span>

                                @endforeach

                            </div>

                        </div>

                    @endif


                    @if($bet->countries->isNotEmpty())

                        <div class="mt-4">

                            <div class="text-xs wr-muted">
                                Countries
                            </div>

                            <div class="mt-2 flex flex-wrap gap-2">

                                @foreach($bet->countries as $country)

                                    <span
                                        class="rounded-full border px-3 py-1 text-xs wr-text"
                                        style="border-color: var(--wr-border);"
                                    >
                                        {{ $country->name }}
                                    </span>

                                @endforeach

                            </div>

                        </div>

                    @endif

                </section>

            @endif


            {{-- LANGUAGES --}}

            @if($availableLocales->isNotEmpty())

                <section
                    class="rounded-2xl border p-5"
                    style="border-color: var(--wr-border);"
                >

                    <h2 class="font-semibold wr-text">
                        Available languages
                    </h2>

                    <div class="mt-4 flex flex-wrap gap-2">

                        @foreach($availableLocales as $availableLocale)

                            <button
                                type="button"
                                wire:click="$set('locale', '{{ $availableLocale }}')"
                                class="rounded-full border px-3 py-1 text-xs font-medium transition
                                    {{ $locale === $availableLocale
                                        ? 'wr-text'
                                        : 'wr-muted'
                                    }}"
                                style="border-color: var(--wr-border);"
                            >
                                {{ strtoupper($availableLocale) }}
                            </button>

                        @endforeach

                    </div>

                </section>

            @endif

        </div>

    </div>

</div>
