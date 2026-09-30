<?php

use App\Enums\UserRole;
use App\Models\Merchant;
use App\Models\MerchantToken;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Livewire\Component;
use App\Services\MerchantSubscriptionService;
use Illuminate\Support\Facades\DB;

new class extends Component
{
    public int $userId;

    public bool $showCreateForm = false;

    public string $merchantName = '';
    public string $marketUrl = '';
    public string $callbackUrl = '';

    public ?int $editingMerchantId = null;
    public string $editMarketUrl = '';
    public string $editCallbackUrl = '';

    public ?int $visibleTokenId = null;
    public ?string $visibleToken = null;


    public function mount(int $userId): void
    {
        $this->userId = $userId;

        $user = User::findOrFail($userId);

        abort_unless(
            $user->role === UserRole::MERCHANT,
            404
        );
    }


    /*
    |--------------------------------------------------------------------------
    | CREATE FORM
    |--------------------------------------------------------------------------
    */

    public function openCreateForm(): void
    {
        $this->reset([
            'merchantName',
            'marketUrl',
            'callbackUrl',
        ]);

        $this->resetValidation();

        $this->showCreateForm = true;
    }


    public function cancelCreateForm(): void
    {
        $this->showCreateForm = false;

        $this->reset([
            'merchantName',
            'marketUrl',
            'callbackUrl',
        ]);

        $this->resetValidation();
    }


    /*
    |--------------------------------------------------------------------------
    | CREATE MERCHANT
    |--------------------------------------------------------------------------
    */

    public function createMerchant(
        MerchantSubscriptionService $subscriptionService
    ): void {
        $this->validate([
            'merchantName' => [
                'required',
                'string',
                'max:255',
            ],

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

        $result = DB::transaction(function () use ($subscriptionService) {

            /*
             * 1. Create Merchant.
             */
            $merchant = Merchant::create([
                'user_id' => $this->userId,
                'name' => trim($this->merchantName),

                'market_url' => $this->marketUrl
                    ? trim($this->marketUrl)
                    : null,

                'callback_url' => $this->callbackUrl
                    ? trim($this->callbackUrl)
                    : null,

                // Legacy field. New delivery logic uses market_url/callback_url.
                'webhook_url' => null,

                'is_paid' => false,
            ]);

            /*
             * 2. Initialize billing.
             *
             * Creates:
             * - USD wallet with balance 0.00
             * - active default Start subscription
             * - change_reason = merchant_created
             */
            $subscriptionService->initializeMerchant($merchant);

            /*
             * 3. Create first API token.
             */
            $plainToken = 'wr_live_' . bin2hex(random_bytes(32));

            $token = MerchantToken::create([
                'merchant_id' => $merchant->id,

                'name' => 'Main API Token',

                'token_prefix' => substr(
                    $plainToken,
                    0,
                    16
                ),

                'token_hash' => hash(
                    'sha256',
                    $plainToken
                ),

                'token_encrypted' => Crypt::encryptString(
                    $plainToken
                ),

                'is_active' => true,
            ]);

            return [
                'merchant' => $merchant,
                'token' => $token,
                'plainToken' => $plainToken,
            ];
        }, 3);

        /*
         * Show newly generated API token.
         */
        $this->visibleTokenId = $result['token']->id;
        $this->visibleToken = $result['plainToken'];

        $this->showCreateForm = false;

        $this->reset([
            'merchantName',
            'marketUrl',
            'callbackUrl',
        ]);

        session()->flash(
            'status',
            'Merchant created successfully with Start billing plan.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | EDIT ENDPOINTS
    |--------------------------------------------------------------------------
    */

    public function editEndpoints(int $merchantId): void
    {
        $merchant = Merchant::query()
            ->where('user_id', $this->userId)
            ->findOrFail($merchantId);

        $this->editingMerchantId = $merchant->id;
        $this->editMarketUrl = $merchant->market_url ?? '';
        $this->editCallbackUrl = $merchant->callback_url ?? '';

        $this->resetValidation();
    }


    public function cancelEditEndpoints(): void
    {
        $this->editingMerchantId = null;
        $this->editMarketUrl = '';
        $this->editCallbackUrl = '';

        $this->resetValidation();
    }


    public function saveEndpoints(): void
    {
        if (! $this->editingMerchantId) {
            return;
        }

        $this->validate([
            'editMarketUrl' => [
                'nullable',
                'url',
                'max:2048',
            ],

            'editCallbackUrl' => [
                'nullable',
                'url',
                'max:2048',
            ],
        ]);

        $merchant = Merchant::query()
            ->where('user_id', $this->userId)
            ->findOrFail($this->editingMerchantId);

        $merchant->update([
            'market_url' => $this->editMarketUrl !== ''
                ? trim($this->editMarketUrl)
                : null,

            'callback_url' => $this->editCallbackUrl !== ''
                ? trim($this->editCallbackUrl)
                : null,
        ]);

        $this->cancelEditEndpoints();

        session()->flash(
            'status',
            'Merchant endpoints updated successfully.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | SHOW TOKEN
    |--------------------------------------------------------------------------
    */

    public function showToken(int $tokenId): void
    {
        $token = MerchantToken::query()
            ->whereHas(
                'merchant',
                fn ($query) => $query->where(
                    'user_id',
                    $this->userId
                )
            )
            ->findOrFail($tokenId);


        if (! $token->token_encrypted) {

            $this->addError(
                'token',
                'The original value of this token is not available.'
            );

            return;
        }


        $this->visibleTokenId = $token->id;

        $this->visibleToken = Crypt::decryptString(
            $token->token_encrypted
        );
    }


    public function hideToken(): void
    {
        $this->visibleTokenId = null;
        $this->visibleToken = null;
    }


    /*
    |--------------------------------------------------------------------------
    | DATA
    |--------------------------------------------------------------------------
    */

    public function with(): array
    {
        return [

            'user' => User::findOrFail(
                $this->userId
            ),

            'merchants' => Merchant::query()
                ->where(
                    'user_id',
                    $this->userId
                )
                ->with([
                    'tokens' => fn ($query) =>
                    $query->orderBy('id', 'desc'),
                ])
                ->orderBy('id', 'desc')
                ->get(),

        ];
    }
};

?>


<div>

    <!-- HEADER -->

    <div class="mb-8 flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">

        <div>

            <a
                href="{{ route('admin.users.index') }}"
                class="text-sm font-bold wr-muted transition hover:text-lime-500"
            >
                ← Users
            </a>

            <div class="mt-5 text-xs font-black uppercase tracking-[0.18em] text-lime-500">
                Merchant Management
            </div>

            <h1 class="mt-2 text-3xl font-black tracking-tight wr-text">
                {{ $user->email }}
            </h1>

            <p class="mt-2 text-sm wr-muted">
                Manage merchants and API credentials for this user.
            </p>

        </div>


        <button
            type="button"
            wire:click="openCreateForm"
            class="
                inline-flex
                items-center
                justify-center
                rounded-xl
                bg-lime-400
                px-6 py-3.5
                text-sm
                font-black
                text-[#07111f]
                transition
                hover:bg-lime-300
            "
        >
            + Create Merchant
        </button>

    </div>


    <!-- STATUS -->

    @if(session('status'))

        <div
            class="
                mb-6
                rounded-2xl
                border border-lime-400/30
                bg-lime-400/[0.06]
                px-5 py-4
                text-sm
                font-bold
                text-lime-300
            "
        >
            {{ session('status') }}
        </div>

    @endif


    <!-- CREATE FORM -->

    @if($showCreateForm)

        <div
            class="
                mb-8
                rounded-3xl
                border border-[var(--wr-border)]
                wr-panel
                p-8
            "
        >

            <div class="flex items-center justify-between">

                <div>

                    <div class="text-xs font-black uppercase tracking-[0.18em] text-lime-500">
                        New Merchant
                    </div>

                    <h2 class="mt-2 text-2xl font-black wr-text">
                        Create Merchant
                    </h2>

                </div>


                <button
                    type="button"
                    wire:click="cancelCreateForm"
                    class="text-sm font-bold wr-muted hover:wr-text"
                >
                    Close
                </button>

            </div>


            <form
                wire:submit="createMerchant"
                class="mt-7"
            >

                <div class="grid gap-5 lg:grid-cols-3">

                    <!-- NAME -->

                    <div>

                        <label class="mb-2 block text-sm font-bold wr-text">
                            Merchant Name *
                        </label>

                        <input
                            type="text"
                            wire:model="merchantName"
                            placeholder="Casino Brand"
                            class="
                                w-full
                                rounded-xl
                                border border-[var(--wr-border)]
                                bg-[var(--wr-input)]
                                px-4 py-3.5
                                wr-text
                                outline-none
                                placeholder:wr-muted
                                focus:border-lime-400/50
                            "
                        >

                        @error('merchantName')

                        <div class="mt-2 text-xs font-bold text-red-400">
                            {{ $message }}
                        </div>

                        @enderror

                    </div>


                    <!-- MARKET URL -->

                    <div>

                        <label class="mb-2 block text-sm font-bold wr-text">
                            Market URL
                        </label>

                        <input
                            type="url"
                            wire:model="marketUrl"
                            placeholder="https://example.com/api/wrangle/markets"
                            class="
                                w-full
                                rounded-xl
                                border border-[var(--wr-border)]
                                bg-[var(--wr-input)]
                                px-4 py-3.5
                                wr-text
                                outline-none
                                placeholder:wr-muted
                                focus:border-lime-400/50
                            "
                        >

                        <div class="mt-2 text-xs wr-muted">
                            New published markets will be delivered to this URL.
                        </div>

                        @error('marketUrl')

                        <div class="mt-2 text-xs font-bold text-red-400">
                            {{ $message }}
                        </div>

                        @enderror

                    </div>


                    <!-- CALLBACK URL -->

                    <div>

                        <label class="mb-2 block text-sm font-bold wr-text">
                            Callback URL
                        </label>

                        <input
                            type="url"
                            wire:model="callbackUrl"
                            placeholder="https://example.com/api/wrangle/callback"
                            class="
                                w-full
                                rounded-xl
                                border border-[var(--wr-border)]
                                bg-[var(--wr-input)]
                                px-4 py-3.5
                                wr-text
                                outline-none
                                placeholder:wr-muted
                                focus:border-lime-400/50
                            "
                        >

                        <div class="mt-2 text-xs wr-muted">
                            Resolution and market result callbacks will be delivered to this URL.
                        </div>

                        @error('callbackUrl')

                        <div class="mt-2 text-xs font-bold text-red-400">
                            {{ $message }}
                        </div>

                        @enderror

                    </div>

                </div>


                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="createMerchant"
                    class="
                        mt-6
                        rounded-xl
                        bg-lime-400
                        px-6 py-3.5
                        font-black
                        text-[#07111f]
                        transition
                        hover:bg-lime-300
                        disabled:opacity-50
                    "
                >

                    <span
                        wire:loading.remove
                        wire:target="createMerchant"
                    >
                        Create Merchant
                    </span>

                    <span
                        wire:loading
                        wire:target="createMerchant"
                    >
                        Creating...
                    </span>

                </button>

            </form>

        </div>

    @endif


    <!-- TOKEN DISPLAY -->

    @if($visibleToken)

        <div
            class="
                mb-8
                rounded-3xl
                border border-lime-400/30
                bg-lime-400/[0.04]
                p-7
            "
        >

            <div class="flex items-start justify-between gap-5">

                <div class="min-w-0 flex-1">

                    <div class="text-xs font-black uppercase tracking-[0.18em] text-lime-500">
                        API Token
                    </div>

                    <div
                        class="
                            mt-4
                            select-all
                            break-all
                            rounded-xl
                            bg-[var(--wr-input)]
                            p-4
                            font-mono
                            text-sm
                            font-bold
                            text-lime-500
                        "
                    >
                        {{ $visibleToken }}
                    </div>

                    <p class="mt-3 text-xs wr-muted">
                        This token can be viewed again later by an administrator.
                    </p>

                </div>


                <button
                    type="button"
                    wire:click="hideToken"
                    class="text-sm font-bold wr-muted hover:wr-text"
                >
                    Hide
                </button>

            </div>

        </div>

    @endif


    <!-- MERCHANT LIST -->

    <div class="space-y-5">

        @forelse($merchants as $merchant)

            <div
                class="
                    rounded-3xl
                    border border-[var(--wr-border)]
                    wr-panel
                    p-7
                "
            >

                <div
                    class="
                        flex
                        flex-col
                        gap-5
                        lg:flex-row
                        lg:items-start
                        lg:justify-between
                    "
                >

                    <div>

                        <div class="text-xl font-black wr-text">
                            {{ $merchant->name }}
                        </div>

                        <div class="mt-5 grid gap-4 text-sm lg:grid-cols-2">

                            <div>
                                <div class="text-xs font-black uppercase tracking-[0.12em] wr-muted">
                                    Market URL
                                </div>

                                <div class="mt-1 break-all wr-muted">
                                    @if($merchant->market_url)
                                        {{ $merchant->market_url }}
                                    @else
                                        Not configured
                                    @endif
                                </div>
                            </div>

                            <div>
                                <div class="text-xs font-black uppercase tracking-[0.12em] wr-muted">
                                    Callback URL
                                </div>

                                <div class="mt-1 break-all wr-muted">
                                    @if($merchant->callback_url)
                                        {{ $merchant->callback_url }}
                                    @else
                                        Not configured
                                    @endif
                                </div>
                            </div>

                        </div>

                    </div>


                    <div class="flex items-center gap-3">

                        <button
                            type="button"
                            wire:click="editEndpoints({{ $merchant->id }})"
                            class="
                                rounded-xl
                                border border-[var(--wr-border)]
                                px-4 py-2
                                text-xs
                                font-black
                                wr-text
                                transition
                                hover:border-lime-400/30
                                hover:text-lime-500
                            "
                        >
                            Edit Endpoints
                        </button>

                        @if($merchant->is_paid)

                            <span
                                class="
                                    rounded-full
                                    bg-lime-400/10
                                    px-3 py-1.5
                                    text-xs
                                    font-black
                                    text-lime-500
                                "
                            >
                                PAID
                            </span>

                        @else

                            <span
                                class="
                                    rounded-full
                                    bg-amber-400/10
                                    px-3 py-1.5
                                    text-xs
                                    font-black
                                    text-amber-400
                                "
                            >
                                NOT PAID
                            </span>

                        @endif

                    </div>

                </div>


                @if($editingMerchantId === $merchant->id)

                    <div class="mt-6 rounded-2xl border border-lime-400/20 bg-[var(--wr-input)] p-5">

                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <div class="text-xs font-black uppercase tracking-[0.14em] text-lime-500">
                                    Merchant Endpoints
                                </div>

                                <div class="mt-1 text-sm wr-muted">
                                    Configure initial market delivery and resolution callbacks.
                                </div>
                            </div>

                            <button
                                type="button"
                                wire:click="cancelEditEndpoints"
                                class="text-sm font-bold wr-muted transition hover:wr-text"
                            >
                                Cancel
                            </button>
                        </div>

                        <div class="mt-5 grid gap-5 lg:grid-cols-2">

                            <div>
                                <label class="mb-2 block text-sm font-bold wr-text">
                                    Market URL
                                </label>

                                <input
                                    type="url"
                                    wire:model="editMarketUrl"
                                    placeholder="https://example.com/api/wrangle/markets"
                                    class="w-full rounded-xl border border-[var(--wr-border)] wr-panel px-4 py-3.5 wr-text outline-none placeholder:wr-muted focus:border-lime-400/50"
                                >

                                <div class="mt-2 text-xs wr-muted">
                                    New published markets are delivered to this URL.
                                </div>

                                @error('editMarketUrl')
                                <div class="mt-2 text-xs font-bold text-red-400">
                                    {{ $message }}
                                </div>
                                @enderror
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-bold wr-text">
                                    Callback URL
                                </label>

                                <input
                                    type="url"
                                    wire:model="editCallbackUrl"
                                    placeholder="https://example.com/api/wrangle/callback"
                                    class="w-full rounded-xl border border-[var(--wr-border)] wr-panel px-4 py-3.5 wr-text outline-none placeholder:wr-muted focus:border-lime-400/50"
                                >

                                <div class="mt-2 text-xs wr-muted">
                                    Resolution and market result callbacks are delivered to this URL.
                                </div>

                                @error('editCallbackUrl')
                                <div class="mt-2 text-xs font-bold text-red-400">
                                    {{ $message }}
                                </div>
                                @enderror
                            </div>

                        </div>

                        <div class="mt-5 flex justify-end">
                            <button
                                type="button"
                                wire:click="saveEndpoints"
                                wire:loading.attr="disabled"
                                wire:target="saveEndpoints"
                                class="rounded-xl bg-lime-400 px-5 py-3 text-sm font-black text-[#07111f] transition hover:bg-lime-300 disabled:opacity-50"
                            >
                                <span wire:loading.remove wire:target="saveEndpoints">
                                    Save Endpoints
                                </span>

                                <span wire:loading wire:target="saveEndpoints">
                                    Saving...
                                </span>
                            </button>
                        </div>

                    </div>

                @endif


                <!-- TOKENS -->

                <div class="mt-6 border-t border-[var(--wr-border)] pt-5">

                    <div class="mb-3 flex items-center justify-between gap-3">
                        <div class="text-xs font-black uppercase tracking-[0.14em] wr-muted">
                            API Tokens
                        </div>
                        <div class="text-xs font-semibold wr-muted">
                            {{ $merchant->tokens->count() }} {{ $merchant->tokens->count() === 1 ? 'token' : 'tokens' }}
                        </div>
                    </div>


                    @forelse($merchant->tokens as $token)

                        <div
                            class="
                                flex
                                flex-col
                                gap-4
                                rounded-xl
                                bg-[var(--wr-input)]
                                px-4 py-4
                                sm:flex-row
                                sm:items-center
                                sm:justify-between
                            "
                        >

                            <div>

                                <div class="font-bold wr-text">
                                    {{ $token->name ?: 'API Token' }}
                                </div>

                                <div class="mt-1 font-mono text-xs wr-muted">
                                    {{ $token->token_prefix }}••••••••
                                </div>

                            </div>


                            <div class="flex items-center gap-4">

                                @if($token->is_active)

                                    <span class="text-xs font-black text-lime-500">
                                        ACTIVE
                                    </span>

                                @else

                                    <span class="text-xs font-black text-red-400">
                                        DISABLED
                                    </span>

                                @endif


                                <button
                                    type="button"
                                    wire:click="showToken({{ $token->id }})"
                                    class="
                                        rounded-lg
                                        border border-[var(--wr-border)]
                                        px-4 py-2
                                        text-xs
                                        font-black
                                        wr-text
                                        transition
                                        hover:border-lime-400/30
                                        hover:text-lime-500
                                    "
                                >
                                    Show Token
                                </button>

                            </div>

                        </div>

                    @empty

                        <div class="text-sm wr-muted">
                            No API tokens.
                        </div>

                    @endforelse

                </div>

            </div>


        @empty

            <div
                class="
                    rounded-3xl
                    border border-dashed border-[var(--wr-border)]
                    wr-panel
                    px-8 py-16
                    text-center
                "
            >

                <div class="text-lg font-black wr-text">
                    No merchants yet
                </div>

                <p class="mt-2 text-sm wr-muted">
                    Create the first merchant for {{ $user->email }}.
                </p>

            </div>

        @endforelse

    </div>

</div>
