<?php

use App\Services\CurrentMerchant;
use Livewire\Component;

new class extends Component
{
    public $merchant;

    public string $marketUrl = '';

    public string $callbackUrl = '';

    public bool $editing = false;

    public function mount(CurrentMerchant $currentMerchant): void
    {
        $this->merchant = $currentMerchant->get(auth()->user());

        /*
         * Webhook endpoints always belong to one concrete merchant.
         * All Merchants mode is only valid for the account dashboard.
         */
        if (! $this->merchant) {
            session()->flash(
                'error',
                'Select a merchant account before managing webhooks.'
            );

            $this->redirectRoute(
                'merchant.dashboard',
                navigate: true
            );

            return;
        }

        $this->fillFromMerchant();
    }

    public function startEditing(): void
    {
        $this->fillFromMerchant();

        $this->resetValidation();

        $this->editing = true;
    }

    public function cancelEditing(): void
    {
        $this->fillFromMerchant();

        $this->resetValidation();

        $this->editing = false;
    }

    public function save(): void
    {
        if (! $this->merchant) {
            return;
        }

        /*
         * Re-resolve ownership before changing anything.
         *
         * We never trust a merchant ID coming from the browser.
         */
        $merchant = auth()->user()
            ->merchants()
            ->whereKey($this->merchant->id)
            ->firstOrFail();

        $validated = $this->validate([
            'marketUrl' => [
                'nullable',
                'url',
                'max:2048',
            ],

            'callbackUrl' => [
                'nullable',
                'url',
                'max:2048',
            ],
        ]);

        $marketUrl = trim($validated['marketUrl'] ?? '');

        $callbackUrl = trim($validated['callbackUrl'] ?? '');

        $merchant->update([
            'market_url' => $marketUrl !== ''
                ? $marketUrl
                : null,

            'callback_url' => $callbackUrl !== ''
                ? $callbackUrl
                : null,
        ]);

        $this->merchant = $merchant->fresh();

        $this->fillFromMerchant();

        $this->editing = false;

        session()->flash(
            'webhooks-status',
            'Webhook endpoints updated successfully.'
        );
    }

    private function fillFromMerchant(): void
    {
        $this->marketUrl = (string) (
            $this->merchant?->market_url ?? ''
        );

        $this->callbackUrl = (string) (
            $this->merchant?->callback_url ?? ''
        );
    }
};
?>

<div class="space-y-6">

    {{-- ================================================================
         HEADER
    ================================================================= --}}

    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">

        <div>

            <div class="flex flex-wrap items-center gap-3">

                <h2 class="wr-text text-2xl font-black">
                    Webhooks
                </h2>

                @if($merchant)
                    <span
                        class="
                            rounded-full
                            bg-lime-400/15
                            px-2.5 py-1
                            text-[10px]
                            font-black uppercase
                            tracking-wider
                            text-lime-500
                        "
                    >
                        {{ $merchant->name }}
                    </span>
                @endif

            </div>

            <p class="wr-muted mt-2 text-sm">
                Configure where wrangle.win sends markets and resolution callbacks.
            </p>

        </div>

    </div>


    @if(session('webhooks-status'))

        <div
            class="
                rounded-2xl
                border
                border-lime-500/30
                bg-lime-500/10
                px-5 py-4
                text-sm
                font-bold
                text-lime-600
            "
        >
            {{ session('webhooks-status') }}
        </div>

    @endif


    @if($merchant)

        {{-- ============================================================
             MERCHANT
        ============================================================= --}}

        <div class="wr-panel rounded-2xl border p-6">

            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

                <div class="flex items-center gap-4">

                    <div
                        class="
                            flex h-12 w-12
                            shrink-0
                            items-center justify-center
                            rounded-xl
                            bg-lime-400
                            text-base font-black
                            text-[#06111f]
                        "
                    >
                        {{ strtoupper(
                            mb_substr(
                                $merchant->name ?? 'M',
                                0,
                                1
                            )
                        ) }}
                    </div>

                    <div>

                        <div class="wr-text text-base font-black">
                            {{ $merchant->name }}
                        </div>

                        <div class="wr-muted mt-1 text-xs">
                            Merchant #{{ $merchant->id }}
                        </div>

                    </div>

                </div>


                @if(! $editing)

                    <button
                        type="button"
                        wire:click="startEditing"
                        class="
                            rounded-xl
                            bg-lime-400
                            px-4 py-2.5
                            text-sm font-black
                            text-[#06111f]
                            transition
                            hover:bg-lime-300
                        "
                    >
                        Edit Endpoints
                    </button>

                @endif

            </div>

        </div>


        {{-- ============================================================
             ENDPOINTS
        ============================================================= --}}

        <div class="grid gap-6 xl:grid-cols-2">

            {{-- MARKET URL --}}
            <div class="wr-panel rounded-2xl border p-6">

                <div class="flex items-start justify-between gap-4">

                    <div>

                        <div
                            class="
                                wr-muted
                                text-[10px]
                                font-bold uppercase
                                tracking-[0.16em]
                            "
                        >
                            Market Delivery
                        </div>

                        <h3 class="wr-text mt-2 text-lg font-black">
                            Market URL
                        </h3>

                    </div>

                    <span
                        class="
                            rounded-full
                            bg-lime-400/10
                            px-2.5 py-1
                            text-[10px]
                            font-black uppercase
                            tracking-wider
                            text-lime-500
                        "
                    >
                        Markets
                    </span>

                </div>


                <p class="wr-muted mt-3 text-sm leading-6">
                    New published prediction markets are delivered to this endpoint.
                </p>


                @if($editing)

                    <div class="mt-6">

                        <label class="wr-text block text-sm font-bold">
                            Market URL
                        </label>

                        <input
                            type="url"
                            wire:model="marketUrl"
                            placeholder="https://merchant.example.com/api/markets"
                            class="
                                wr-text
                                mt-2
                                w-full
                                rounded-xl
                                border
                                px-4 py-3
                                text-sm
                                outline-none
                                transition
                                focus:border-lime-400
                            "
                            style="
                                background: var(--wr-input);
                                border-color: var(--wr-border);
                            "
                        >

                        @error('marketUrl')
                        <div class="mt-2 text-xs font-bold text-red-500">
                            {{ $message }}
                        </div>
                        @enderror

                    </div>

                @else

                    <div
                        class="
                            mt-6
                            rounded-xl
                            border
                            p-4
                        "
                        style="
                            border-color: var(--wr-border);
                            background: var(--wr-panel-secondary);
                        "
                    >

                        @if($merchant->market_url)

                            <div class="wr-text break-all font-mono text-sm">
                                {{ $merchant->market_url }}
                            </div>

                        @else

                            <div class="wr-muted text-sm">
                                Not configured
                            </div>

                        @endif

                    </div>

                @endif


                <div
                    class="
                        wr-muted
                        mt-5
                        border-t
                        pt-4
                        text-xs
                        leading-5
                    "
                    style="border-color: var(--wr-border);"
                >
                    Used when wrangle.win delivers a newly published market to
                    {{ $merchant->name }}.
                </div>

            </div>


            {{-- CALLBACK URL --}}
            <div class="wr-panel rounded-2xl border p-6">

                <div class="flex items-start justify-between gap-4">

                    <div>

                        <div
                            class="
                                wr-muted
                                text-[10px]
                                font-bold uppercase
                                tracking-[0.16em]
                            "
                        >
                            Result Delivery
                        </div>

                        <h3 class="wr-text mt-2 text-lg font-black">
                            Callback URL
                        </h3>

                    </div>

                    <span
                        class="
                            rounded-full
                            bg-lime-400/10
                            px-2.5 py-1
                            text-[10px]
                            font-black uppercase
                            tracking-wider
                            text-lime-500
                        "
                    >
                        Results
                    </span>

                </div>


                <p class="wr-muted mt-3 text-sm leading-6">
                    Market resolutions and result callbacks are delivered to this endpoint.
                </p>


                @if($editing)

                    <div class="mt-6">

                        <label class="wr-text block text-sm font-bold">
                            Callback URL
                        </label>

                        <input
                            type="url"
                            wire:model="callbackUrl"
                            placeholder="https://merchant.example.com/api/market-results"
                            class="
                                wr-text
                                mt-2
                                w-full
                                rounded-xl
                                border
                                px-4 py-3
                                text-sm
                                outline-none
                                transition
                                focus:border-lime-400
                            "
                            style="
                                background: var(--wr-input);
                                border-color: var(--wr-border);
                            "
                        >

                        @error('callbackUrl')
                        <div class="mt-2 text-xs font-bold text-red-500">
                            {{ $message }}
                        </div>
                        @enderror

                    </div>

                @else

                    <div
                        class="
                            mt-6
                            rounded-xl
                            border
                            p-4
                        "
                        style="
                            border-color: var(--wr-border);
                            background: var(--wr-panel-secondary);
                        "
                    >

                        @if($merchant->callback_url)

                            <div class="wr-text break-all font-mono text-sm">
                                {{ $merchant->callback_url }}
                            </div>

                        @else

                            <div class="wr-muted text-sm">
                                Not configured
                            </div>

                        @endif

                    </div>

                @endif


                <div
                    class="
                        wr-muted
                        mt-5
                        border-t
                        pt-4
                        text-xs
                        leading-5
                    "
                    style="border-color: var(--wr-border);"
                >
                    Used when a delivered market is resolved and wrangle.win
                    sends the winning result to {{ $merchant->name }}.
                </div>

            </div>

        </div>


        {{-- ============================================================
             SAVE
        ============================================================= --}}

        @if($editing)

            <div class="wr-panel rounded-2xl border p-5">

                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

                    <div>

                        <div class="wr-text text-sm font-black">
                            Save endpoint configuration
                        </div>

                        <div class="wr-muted mt-1 text-xs">
                            These endpoints apply only to {{ $merchant->name }}
                            (Merchant #{{ $merchant->id }}).
                        </div>

                    </div>


                    <div class="flex gap-3">

                        <button
                            type="button"
                            wire:click="cancelEditing"
                            class="
                                wr-text
                                rounded-xl
                                border
                                px-4 py-2.5
                                text-sm font-bold
                                transition
                            "
                            style="border-color: var(--wr-border);"
                        >
                            Cancel
                        </button>

                        <button
                            type="button"
                            wire:click="save"
                            wire:loading.attr="disabled"
                            wire:target="save"
                            class="
                                rounded-xl
                                bg-lime-400
                                px-5 py-2.5
                                text-sm font-black
                                text-[#06111f]
                                transition
                                hover:bg-lime-300
                                disabled:opacity-50
                            "
                        >
                            <span wire:loading.remove wire:target="save">
                                Save Endpoints
                            </span>

                            <span wire:loading wire:target="save">
                                Saving...
                            </span>
                        </button>

                    </div>

                </div>

            </div>

        @endif


        {{-- ============================================================
             DELIVERY FLOW
        ============================================================= --}}

        <div class="wr-panel rounded-2xl border p-6">

            <h3 class="wr-text text-lg font-black">
                Delivery Flow
            </h3>

            <p class="wr-muted mt-1 text-sm">
                How wrangle.win communicates with {{ $merchant->name }}.
            </p>


            <div class="mt-6 grid gap-4 lg:grid-cols-[1fr_auto_1fr_auto_1fr] lg:items-center">

                <div
                    class="rounded-xl border p-4"
                    style="
                        border-color: var(--wr-border);
                        background: var(--wr-panel-secondary);
                    "
                >
                    <div class="wr-muted text-[10px] font-bold uppercase tracking-wider">
                        Step 1
                    </div>

                    <div class="wr-text mt-2 text-sm font-black">
                        Market Published
                    </div>

                    <div class="wr-muted mt-1 text-xs">
                        wrangle.win prepares a market.
                    </div>
                </div>


                <div class="hidden text-center text-xl font-black text-lime-500 lg:block">
                    →
                </div>


                <div
                    class="rounded-xl border p-4"
                    style="
                        border-color: var(--wr-border);
                        background: var(--wr-panel-secondary);
                    "
                >
                    <div class="wr-muted text-[10px] font-bold uppercase tracking-wider">
                        Step 2
                    </div>

                    <div class="wr-text mt-2 text-sm font-black">
                        Market URL
                    </div>

                    <div class="wr-muted mt-1 text-xs">
                        Market is delivered to the merchant.
                    </div>
                </div>


                <div class="hidden text-center text-xl font-black text-lime-500 lg:block">
                    →
                </div>


                <div
                    class="rounded-xl border p-4"
                    style="
                        border-color: var(--wr-border);
                        background: var(--wr-panel-secondary);
                    "
                >
                    <div class="wr-muted text-[10px] font-bold uppercase tracking-wider">
                        Step 3
                    </div>

                    <div class="wr-text mt-2 text-sm font-black">
                        Callback URL
                    </div>

                    <div class="wr-muted mt-1 text-xs">
                        Resolution is delivered after settlement.
                    </div>
                </div>

            </div>

        </div>

    @endif

</div>
