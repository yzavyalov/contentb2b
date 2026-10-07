<?php

use Livewire\Component;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Services\CurrentMerchant;

new class extends Component
{
    public $merchant;
    public $tokens;
    public string $tokenName = '';
    public bool $showCreateForm = false;
    public ?string $newPlainToken = null;
    public ?int $visibleTokenId = null;
    public ?string $visiblePlainToken = null;

    public function showToken(int $tokenId): void
    {
        if (! $this->merchant) {
            return;
        }

        $token = $this->merchant
            ->tokens()
            ->whereKey($tokenId)
            ->firstOrFail();

        $this->visibleTokenId = $token->id;
        $this->visiblePlainToken = Crypt::decryptString($token->token_encrypted);
    }

    public function hideToken(): void
    {
        $this->visibleTokenId = null;
        $this->visiblePlainToken = null;
    }

    public function toggleToken(int $tokenId): void
    {
        if (! $this->merchant) {
            return;
        }

        DB::transaction(function () use ($tokenId) {
            $token = $this->merchant
                ->tokens()
                ->whereKey($tokenId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($token->is_active) {
                $token->update([
                    'is_active' => false,
                ]);

                if ($this->visibleTokenId === $token->id) {
                    $this->hideToken();
                }

                return;
            }

            $this->merchant
                ->tokens()
                ->where('is_active', true)
                ->update([
                    'is_active' => false,
                ]);

            $token->update([
                'is_active' => true,
            ]);
        });

        $this->loadTokens();
    }

    public function mount(CurrentMerchant $currentMerchant): void
    {
        $this->merchant = $currentMerchant->get(auth()->user());

        /*
         * API Tokens are always scoped to one concrete merchant.
         * "All Merchants" mode is valid only for the account dashboard.
         */
        if (! $this->merchant) {
            session()->flash(
                'error',
                'Select a merchant account before managing API tokens.'
            );

            $this->redirectRoute(
                'merchant.dashboard',
                navigate: true
            );

            return;
        }

        $this->loadTokens();
    }

    public function loadTokens(): void
    {
        $this->tokens = $this->merchant
            ? $this->merchant->tokens()->latest()->get()
            : collect();
    }

    public function createToken(): void
    {
        if (! $this->merchant) {
            return;
        }

        $this->validate([
            'tokenName' => ['required', 'string', 'min:2', 'max:100'],
        ]);

        $plainToken = 'wr_live_' . Str::random(48);

        DB::transaction(function () use ($plainToken) {
            $this->merchant
                ->tokens()
                ->where('is_active', true)
                ->update([
                    'is_active' => false,
                ]);

            $this->merchant->tokens()->create([
                'name' => $this->tokenName,
                'token_prefix' => substr($plainToken, 0, 15),
                'token_hash' => hash('sha256', $plainToken),
                'token_encrypted' => Crypt::encryptString($plainToken),
                'is_active' => true,
            ]);
        });

        $this->newPlainToken = $plainToken;

        $this->tokenName = '';
        $this->showCreateForm = false;

        $this->loadTokens();
    }
};
?>

<div class="space-y-6">

    <div>
        <h2 class="wr-text text-2xl font-black">
            API Tokens
        </h2>

        <p class="wr-muted mt-2 text-sm">
            Create and manage API tokens used to access wrangle.win services.
        </p>
    </div>

    <div class="wr-panel rounded-2xl border p-6">

        <div class="flex items-center justify-between gap-4">

            <div>
                <h3 class="wr-text text-lg font-black">
                    Your API Tokens
                </h3>

                <p class="wr-muted mt-1 text-sm">
                    Only one API token can be active at a time. Activating or creating a new token automatically disables the previous active token.
                </p>
            </div>

            <button
                type="button"
                wire:click="$set('showCreateForm', true)"
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
                Generate Token
            </button>

        </div>

        @if($showCreateForm)

            <div
                class="mt-6 rounded-xl border p-5"
                style="border-color: var(--wr-border);"
            >
                <label class="wr-text block text-sm font-bold">
                    Token Name
                </label>

                <p class="wr-muted mt-1 text-xs">
                    For example: Production, Development or Staging.
                </p>

                <input
                    type="text"
                    wire:model="tokenName"
                    placeholder="Production"
                    class="
                wr-text
                mt-3
                w-full
                rounded-xl
                border
                px-4 py-3
                text-sm
                outline-none
            "
                    style="
                background: var(--wr-input);
                border-color: var(--wr-border);
            "
                >

                <div class="mt-4 flex gap-3">

                    <button
                        type="button"
                        wire:click="createToken"
                        class="
                    rounded-xl
                    bg-lime-400
                    px-4 py-2.5
                    text-sm font-black
                    text-[#06111f]
                    hover:bg-lime-300
                "
                    >
                        Create Token
                    </button>

                    <button
                        type="button"
                        wire:click="$set('showCreateForm', false)"
                        class="
                    wr-text
                    rounded-xl
                    border
                    px-4 py-2.5
                    text-sm font-bold
                "
                        style="border-color: var(--wr-border);"
                    >
                        Cancel
                    </button>

                </div>
            </div>

        @endif

        @if($newPlainToken)

            <div
                class="mt-6 rounded-xl border border-lime-500/30 bg-lime-500/10 p-5"
            >
                <div class="text-sm font-black text-lime-600">
                    API Token Created
                </div>

                <p class="wr-muted mt-2 text-sm">
                    Copy your token and store it securely.
                </p>

                <div
                    class="mt-4 flex items-center gap-3 rounded-xl border p-3"
                    style="
        background: var(--wr-input);
        border-color: var(--wr-border);
    "
                >
                    <div class="wr-text min-w-0 flex-1 break-all font-mono text-sm">
                        {{ $newPlainToken }}
                    </div>

                    <button
                        type="button"
                        x-data="{ copied: false }"
                        x-on:click="
            navigator.clipboard.writeText(@js($newPlainToken));
            copied = true;
            setTimeout(() => copied = false, 2000);
        "
                        class="
            shrink-0
            rounded-lg
            bg-lime-400
            px-4 py-2
            text-xs font-black
            text-[#06111f]
            transition
            hover:bg-lime-300
        "
                    >
                        <span x-show="!copied">Copy</span>
                        <span x-show="copied" x-cloak>Copied!</span>
                    </button>
                </div>

            </div>

        @endif

        <div class="mt-6 space-y-3">

            @forelse($tokens as $token)

                <div
                    class="flex items-center justify-between gap-4 rounded-xl border p-4"
                    style="border-color: var(--wr-border);"
                >

                    <div class="min-w-0">

                        <div class="wr-text font-bold">
                            {{ $token->name ?? 'API Token' }}
                        </div>

                        <div class="wr-muted mt-1 font-mono text-xs">
                            {{ $token->token_prefix }}************
                        </div>

                        @if($visibleTokenId === $token->id && $visiblePlainToken)

                            <div
                                class="mt-3 flex items-center gap-3 rounded-xl border p-3"
                                style="
            background: var(--wr-input);
            border-color: var(--wr-border);
        "
                            >
                                <div class="wr-text min-w-0 flex-1 break-all font-mono text-xs">
                                    {{ $visiblePlainToken }}
                                </div>

                                <button
                                    type="button"
                                    x-data="{ copied: false }"
                                    x-on:click="
                navigator.clipboard.writeText(@js($visiblePlainToken));
                copied = true;
                setTimeout(() => copied = false, 2000);
            "
                                    class="
                shrink-0
                rounded-lg
                bg-lime-400
                px-3 py-2
                text-xs font-black
                text-[#06111f]
                transition
                hover:bg-lime-300
            "
                                >
                                    <span x-show="!copied">Copy</span>
                                    <span x-show="copied" x-cloak>Copied!</span>
                                </button>
                            </div>

                        @endif

                    </div>

                    <div class="flex shrink-0 items-center gap-3">

                        @if($token->is_active)
                            <span class="text-xs font-bold text-lime-500">
            Active
        </span>
                        @else
                            <span class="wr-muted text-xs font-bold">
            Inactive
        </span>
                        @endif

                        @if($visibleTokenId === $token->id)

                            <button
                                type="button"
                                wire:click="hideToken"
                                class="
                wr-text
                rounded-lg border
                px-3 py-2
                text-xs font-bold
                transition
            "
                                style="border-color: var(--wr-border);"
                            >
                                Hide
                            </button>

                        @else

                            <button
                                type="button"
                                wire:click="showToken({{ $token->id }})"
                                class="
                wr-text
                rounded-lg border
                px-3 py-2
                text-xs font-bold
                transition
            "
                                style="border-color: var(--wr-border);"
                            >
                                Show
                            </button>

                        @endif

                        <button
                            type="button"
                            wire:click="toggleToken({{ $token->id }})"
                            wire:confirm="{{ $token->is_active
    ? 'Disable this API token? Wrangle will stop signing merchant requests until another token is activated.'
    : 'Activate this API token? The currently active token will be disabled immediately.' }}"
                            class="
        rounded-lg border
        px-3 py-2
        text-xs font-bold
        transition

        {{ $token->is_active
            ? 'border-red-500/30 text-red-500 hover:bg-red-500/10'
            : 'border-lime-500/30 text-lime-500 hover:bg-lime-500/10'
        }}
    "
                        >
                            {{ $token->is_active ? 'Disable' : 'Enable' }}
                        </button>

                    </div>

                </div>

            @empty

                <div
                    class="wr-muted rounded-xl border border-dashed p-8 text-center text-sm"
                    style="border-color: var(--wr-border);"
                >
                    No API tokens yet.
                </div>

            @endforelse

        </div>

    </div>

</div>
