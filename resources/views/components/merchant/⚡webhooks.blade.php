<?php

use App\Services\CurrentMerchant;
use App\Services\MerchantSignedHttpService;
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

    public function sendTestMarket(
        MerchantSignedHttpService $signedHttp
    ): void {
        $merchant = $this->resolveMerchant();

        if (! $merchant->market_url) {
            $this->testError(
                'Configure the Market URL before sending a test market.'
            );

            return;
        }

        $payload = $this->testMarketPayload();

        $this->sendTestRequest(
            $signedHttp,
            $merchant,
            $merchant->market_url,
            $payload,
            'Test market delivered successfully.'
        );
    }

    public function sendTestResolving(
        MerchantSignedHttpService $signedHttp
    ): void {
        $merchant = $this->resolveMerchant();

        if (! $merchant->callback_url) {
            $this->testError(
                'Configure the Callback URL before sending a test callback.'
            );

            return;
        }

        $payload = [
            'event' => 'market.resolving',
            'market' => [
                'id' => 900000001,
                'status' => 'resolving',
                'source_locale' => 'en',
                'finish_at' => '2030-01-01T12:00:00Z',
                'published_at' => '2030-01-01T10:00:00Z',
                'resolved_at' => null,
                'winning_answer_id' => null,
            ],
        ];

        $this->sendTestRequest(
            $signedHttp,
            $merchant,
            $merchant->callback_url,
            $payload,
            'Test resolving callback delivered successfully.'
        );
    }

    public function sendTestCancelled(
        MerchantSignedHttpService $signedHttp
    ): void {
        $merchant = $this->resolveMerchant();

        if (! $merchant->callback_url) {
            $this->testError(
                'Configure the Callback URL before sending a test callback.'
            );

            return;
        }

        $payload = [
            'event' => 'market.cancelled',
            'market' => [
                'id' => 900000001,
                'status' => 'cancelled',
                'source_locale' => 'en',
                'finish_at' => '2030-01-01T12:00:00Z',
                'published_at' => '2030-01-01T10:00:00Z',
                'resolved_at' => null,
                'winning_answer_id' => null,
            ],
        ];

        $this->sendTestRequest(
            $signedHttp,
            $merchant,
            $merchant->callback_url,
            $payload,
            'Test cancelled callback delivered successfully.'
        );
    }

    public function sendTestResolved(
        MerchantSignedHttpService $signedHttp
    ): void {
        $merchant = $this->resolveMerchant();

        if (! $merchant->callback_url) {
            $this->testError(
                'Configure the Callback URL before sending a test callback.'
            );

            return;
        }

        $payload = [
            'event' => 'market.resolved',
            'market' => [
                'id' => 900000001,
                'status' => 'resolved',
                'source_locale' => 'en',
                'finish_at' => '2030-01-01T12:00:00Z',
                'published_at' => '2030-01-01T10:00:00Z',
                'resolved_at' => '2030-01-01T12:05:00Z',
                'winning_answer_id' => 900000011,
            ],
        ];

        $this->sendTestRequest(
            $signedHttp,
            $merchant,
            $merchant->callback_url,
            $payload,
            'Test resolved callback delivered successfully.'
        );
    }

    private function resolveMerchant()
    {
        if (! $this->merchant) {
            abort(404);
        }

        return auth()->user()
            ->merchants()
            ->whereKey($this->merchant->id)
            ->firstOrFail();
    }

    private function sendTestRequest(
        MerchantSignedHttpService $signedHttp,
                                  $merchant,
        string $url,
        array $payload,
        string $successMessage
    ): void {
        try {
            $response = $signedHttp->post(
                $merchant,
                $url,
                $payload
            );

            if (! $response->successful()) {
                $this->testError(
                    'Merchant endpoint returned HTTP '
                    .$response->status().'.'
                );

                return;
            }

            session()->flash(
                'webhooks-test-status',
                $successMessage
            );
        } catch (\Throwable $e) {
            report($e);

            $this->testError(
                'Test delivery failed. Check the endpoint and try again.'
            );
        }
    }

    private function testError(string $message): void
    {
        session()->flash(
            'webhooks-test-error',
            $message
        );
    }

    private function testMarketPayload(): array
    {
        return [
            'event' => 'market.published',

            'market' => [
                'id' => 900000001,
                'status' => 'published',
                'source_locale' => 'en',
                'finish_at' => '2030-01-01T12:00:00Z',
                'published_at' => '2030-01-01T10:00:00Z',
                'resolved_at' => null,
                'winning_answer_id' => null,
            ],

            'translations' => [
                [
                    'locale' => 'en',
                    'title' => 'Will Team Alpha win the test match?',
                    'description' => 'Synthetic market generated by the wrangle.win webhook tester.',
                ],
                [
                    'locale' => 'es',
                    'title' => '¿Ganará Team Alpha el partido de prueba?',
                    'description' => 'Mercado sintético generado por el probador de webhooks de wrangle.win.',
                ],
            ],

            'answers' => [
                [
                    'id' => 900000011,
                    'sort_order' => 0,
                    'translations' => [
                        [
                            'locale' => 'en',
                            'title' => 'Team Alpha will win',
                        ],
                        [
                            'locale' => 'es',
                            'title' => 'Ganará Team Alpha',
                        ],
                    ],
                ],
                [
                    'id' => 900000012,
                    'sort_order' => 1,
                    'translations' => [
                        [
                            'locale' => 'en',
                            'title' => 'Team Beta will win',
                        ],
                        [
                            'locale' => 'es',
                            'title' => 'Ganará Team Beta',
                        ],
                    ],
                ],
                [
                    'id' => 900000013,
                    'sort_order' => 2,
                    'translations' => [
                        [
                            'locale' => 'en',
                            'title' => 'The match will end in a draw',
                        ],
                        [
                            'locale' => 'es',
                            'title' => 'El partido terminará en empate',
                        ],
                    ],
                ],
            ],
        ];
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

    {{-- HEADER --}}
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
                Configure webhook endpoints and test your wrangle.win integration.
            </p>
        </div>
    </div>


    {{-- STATUS --}}
    @if(session('webhooks-status'))
        <div
            class="
                rounded-2xl border border-lime-500/30
                bg-lime-500/10 px-5 py-4
                text-sm font-bold text-lime-600
            "
        >
            {{ session('webhooks-status') }}
        </div>
    @endif

    @if(session('webhooks-test-status'))
        <div
            class="
                rounded-2xl border border-lime-500/30
                bg-lime-500/10 px-5 py-4
                text-sm font-bold text-lime-600
            "
        >
            {{ session('webhooks-test-status') }}
        </div>
    @endif

    @if(session('webhooks-test-error'))
        <div
            class="
                rounded-2xl border border-red-500/30
                bg-red-500/10 px-5 py-4
                text-sm font-bold text-red-500
            "
        >
            {{ session('webhooks-test-error') }}
        </div>
    @endif


    @if($merchant)

        {{-- MERCHANT --}}
        <div class="wr-panel rounded-2xl border p-6">

            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

                <div class="flex items-center gap-4">

                    <div
                        class="
                            flex h-12 w-12 shrink-0
                            items-center justify-center
                            rounded-xl bg-lime-400
                            text-base font-black text-[#06111f]
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
                            rounded-xl bg-lime-400
                            px-4 py-2.5
                            text-sm font-black
                            text-[#06111f]
                            transition hover:bg-lime-300
                        "
                    >
                        Edit Endpoints
                    </button>
                @endif

            </div>

        </div>


        {{-- ENDPOINTS --}}
        <div class="grid gap-6 xl:grid-cols-2">

            {{-- MARKET URL --}}
            <div class="wr-panel rounded-2xl border p-6">

                <div class="flex items-start justify-between gap-4">

                    <div>
                        <div
                            class="
                                wr-muted
                                text-[10px] font-bold uppercase
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
                            rounded-full bg-lime-400/10
                            px-2.5 py-1
                            text-[10px] font-black uppercase
                            tracking-wider text-lime-500
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
                                wr-text mt-2 w-full
                                rounded-xl border
                                px-4 py-3 text-sm
                                outline-none transition
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
                        class="mt-6 rounded-xl border p-4"
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
                    class="wr-muted mt-5 border-t pt-4 text-xs leading-5"
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
                                text-[10px] font-bold uppercase
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
                            rounded-full bg-lime-400/10
                            px-2.5 py-1
                            text-[10px] font-black uppercase
                            tracking-wider text-lime-500
                        "
                    >
                        Results
                    </span>

                </div>


                <p class="wr-muted mt-3 text-sm leading-6">
                    Market lifecycle callbacks are delivered to this endpoint.
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
                                wr-text mt-2 w-full
                                rounded-xl border
                                px-4 py-3 text-sm
                                outline-none transition
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
                        class="mt-6 rounded-xl border p-4"
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
                    class="wr-muted mt-5 border-t pt-4 text-xs leading-5"
                    style="border-color: var(--wr-border);"
                >
                    Used for resolving, resolved and cancelled market callbacks.
                </div>

            </div>

        </div>


        {{-- SAVE --}}
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
                                wr-text rounded-xl border
                                px-4 py-2.5
                                text-sm font-bold transition
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
                                rounded-xl bg-lime-400
                                px-5 py-2.5
                                text-sm font-black
                                text-[#06111f]
                                transition hover:bg-lime-300
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


        {{-- WEBHOOK TESTER --}}
        <div class="wr-panel rounded-2xl border p-6">

            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">

                <div>
                    <div
                        class="
                            wr-muted
                            text-[10px] font-bold uppercase
                            tracking-[0.16em]
                        "
                    >
                        Integration Testing
                    </div>

                    <h3 class="wr-text mt-2 text-lg font-black">
                        Webhook Tester
                    </h3>

                    <p class="wr-muted mt-2 max-w-3xl text-sm leading-6">
                        Send signed test requests using the same authentication
                        and request format as live wrangle.win webhooks.
                        Test data does not create or modify live markets.
                    </p>
                </div>

                <span
                    class="
                        rounded-full bg-amber-400/10
                        px-2.5 py-1
                        text-[10px] font-black uppercase
                        tracking-wider text-amber-500
                    "
                >
                    Test Data
                </span>

            </div>


            <div
                class="
                    mt-6 rounded-xl border p-4
                    text-xs leading-5
                "
                style="
                    border-color: var(--wr-border);
                    background: var(--wr-panel-secondary);
                "
            >
                <div class="wr-text font-black">
                    Test Market ID: 900000001
                </div>

                <div class="wr-muted mt-1">
                    Test Answer IDs: 900000011, 900000012, 900000013.
                    These identifiers are reserved for integration testing.
                </div>
            </div>


            <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">

                {{-- PUBLISHED --}}
                <div
                    class="rounded-xl border p-5"
                    style="
                        border-color: var(--wr-border);
                        background: var(--wr-panel-secondary);
                    "
                >
                    <div class="wr-muted text-[10px] font-bold uppercase tracking-wider">
                        Market URL
                    </div>

                    <div class="wr-text mt-2 text-sm font-black">
                        Test Market
                    </div>

                    <div class="wr-muted mt-2 min-h-10 text-xs leading-5">
                        Sends a complete synthetic published market with
                        translations and three answers.
                    </div>

                    <button
                        type="button"
                        wire:click="sendTestMarket"
                        wire:loading.attr="disabled"
                        wire:target="sendTestMarket"
                        class="
                            mt-5 w-full rounded-xl
                            bg-lime-400 px-4 py-2.5
                            text-xs font-black
                            text-[#06111f]
                            transition hover:bg-lime-300
                            disabled:opacity-50
                        "
                    >
                        <span wire:loading.remove wire:target="sendTestMarket">
                            Send Test Market
                        </span>

                        <span wire:loading wire:target="sendTestMarket">
                            Sending...
                        </span>
                    </button>
                </div>


                {{-- RESOLVING --}}
                <div
                    class="rounded-xl border p-5"
                    style="
                        border-color: var(--wr-border);
                        background: var(--wr-panel-secondary);
                    "
                >
                    <div class="wr-muted text-[10px] font-bold uppercase tracking-wider">
                        Callback URL
                    </div>

                    <div class="wr-text mt-2 text-sm font-black">
                        Test Resolving
                    </div>

                    <div class="wr-muted mt-2 min-h-10 text-xs leading-5">
                        Simulates the callback sent when the market reaches
                        its finish time and enters resolving.
                    </div>

                    <button
                        type="button"
                        wire:click="sendTestResolving"
                        wire:loading.attr="disabled"
                        wire:target="sendTestResolving"
                        class="
                            mt-5 w-full rounded-xl border
                            px-4 py-2.5
                            text-xs font-black
                            text-lime-500
                            transition
                            hover:bg-lime-400/10
                            disabled:opacity-50
                        "
                        style="border-color: var(--wr-border);"
                    >
                        <span wire:loading.remove wire:target="sendTestResolving">
                            Send Resolving
                        </span>

                        <span wire:loading wire:target="sendTestResolving">
                            Sending...
                        </span>
                    </button>
                </div>


                {{-- CANCELLED --}}
                <div
                    class="rounded-xl border p-5"
                    style="
                        border-color: var(--wr-border);
                        background: var(--wr-panel-secondary);
                    "
                >
                    <div class="wr-muted text-[10px] font-bold uppercase tracking-wider">
                        Callback URL
                    </div>

                    <div class="wr-text mt-2 text-sm font-black">
                        Test Cancelled
                    </div>

                    <div class="wr-muted mt-2 min-h-10 text-xs leading-5">
                        Simulates a cancelled market callback without
                        a winning answer.
                    </div>

                    <button
                        type="button"
                        wire:click="sendTestCancelled"
                        wire:loading.attr="disabled"
                        wire:target="sendTestCancelled"
                        class="
                            mt-5 w-full rounded-xl border
                            px-4 py-2.5
                            text-xs font-black
                            text-amber-500
                            transition
                            hover:bg-amber-400/10
                            disabled:opacity-50
                        "
                        style="border-color: var(--wr-border);"
                    >
                        <span wire:loading.remove wire:target="sendTestCancelled">
                            Send Cancelled
                        </span>

                        <span wire:loading wire:target="sendTestCancelled">
                            Sending...
                        </span>
                    </button>
                </div>


                {{-- RESOLVED --}}
                <div
                    class="rounded-xl border p-5"
                    style="
                        border-color: var(--wr-border);
                        background: var(--wr-panel-secondary);
                    "
                >
                    <div class="wr-muted text-[10px] font-bold uppercase tracking-wider">
                        Callback URL
                    </div>

                    <div class="wr-text mt-2 text-sm font-black">
                        Test Resolved
                    </div>

                    <div class="wr-muted mt-2 min-h-10 text-xs leading-5">
                        Sends a resolved callback with test answer
                        900000011 marked as the winner.
                    </div>

                    <button
                        type="button"
                        wire:click="sendTestResolved"
                        wire:loading.attr="disabled"
                        wire:target="sendTestResolved"
                        class="
                            mt-5 w-full rounded-xl border
                            px-4 py-2.5
                            text-xs font-black
                            text-lime-500
                            transition
                            hover:bg-lime-400/10
                            disabled:opacity-50
                        "
                        style="border-color: var(--wr-border);"
                    >
                        <span wire:loading.remove wire:target="sendTestResolved">
                            Send Resolved
                        </span>

                        <span wire:loading wire:target="sendTestResolved">
                            Sending...
                        </span>
                    </button>
                </div>

            </div>


            <div
                class="wr-muted mt-6 border-t pt-4 text-xs leading-5"
                style="border-color: var(--wr-border);"
            >
                Test requests are signed with the merchant's active API token
                using the same HMAC mechanism as production requests.
            </div>

        </div>


        {{-- DELIVERY FLOW --}}
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
                        Resolving, resolved or cancelled callbacks are delivered.
                    </div>
                </div>

            </div>

        </div>

    @endif

</div>
