<?php

use App\Enums\MerchantSubscriptionStatus;
use App\Enums\MerchantSubscriptionChangeReason;
use App\Models\BillingPlan;
use App\Models\Merchant;
use App\Models\MerchantSubscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;
use App\Models\MerchantBillingPeriod;
use App\Enums\MerchantBillingPeriodStatus;

new class extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public string $planFilter = '';

    public bool $showSubscriptionForm = false;
    public ?int $editingMerchantId = null;
    public ?int $editingSubscriptionId = null;

    public string $billingPlanId = '';
    public string $subscriptionStatus = 'active';
    public string $startsAt = '';
    public string $endsAt = '';

    public string $customMonthlyFee = '';
    public string $customPricePerMarket = '';
    public string $customIncludedMarkets = '';
    public string $customOveragePrice = '';

    public bool $useCustomMonthlyFee = false;
    public bool $useCustomPricePerMarket = false;
    public bool $useCustomIncludedMarkets = false;
    public bool $useCustomOveragePrice = false;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingPlanFilter(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->statusFilter = '';
        $this->planFilter = '';
        $this->resetPage();
    }

    public function updatedBillingPlanId($value): void
    {
        if (! $this->editingSubscriptionId || ! $value) {
            return;
        }

        $subscription = MerchantSubscription::find($this->editingSubscriptionId);

        if (! $subscription) {
            return;
        }

        if ((int) $subscription->billing_plan_id !== (int) $value) {
            $this->startsAt = now()->format('Y-m-d');
            $this->endsAt = '';
        } else {
            $this->startsAt = $subscription->starts_at?->format('Y-m-d') ?? now()->format('Y-m-d');
            $this->endsAt = $subscription->ends_at?->format('Y-m-d') ?? '';
        }
    }

    public function manageSubscription(int $merchantId): void
    {
        $merchant = Merchant::query()
            ->with([
                'subscriptions' => fn ($query) => $query->latest('id'),
            ])
            ->findOrFail($merchantId);

        $subscription = $merchant->subscriptions->first();

        $this->resetSubscriptionForm();

        $this->editingMerchantId = $merchant->id;

        if ($subscription) {
            $this->editingSubscriptionId = $subscription->id;
            $this->billingPlanId = (string) $subscription->billing_plan_id;
            $this->subscriptionStatus = $subscription->status->value;
            $this->startsAt = $subscription->starts_at?->format('Y-m-d') ?? '';
            $this->endsAt = $subscription->ends_at?->format('Y-m-d') ?? '';

            $this->useCustomMonthlyFee = $subscription->custom_monthly_fee !== null;
            $this->customMonthlyFee = $subscription->custom_monthly_fee !== null
                ? (string) $subscription->custom_monthly_fee
                : '';

            $this->useCustomPricePerMarket = $subscription->custom_price_per_market !== null;
            $this->customPricePerMarket = $subscription->custom_price_per_market !== null
                ? (string) $subscription->custom_price_per_market
                : '';

            $this->useCustomIncludedMarkets = $subscription->custom_included_markets !== null;
            $this->customIncludedMarkets = $subscription->custom_included_markets !== null
                ? (string) $subscription->custom_included_markets
                : '';

            $this->useCustomOveragePrice = $subscription->custom_overage_price !== null;
            $this->customOveragePrice = $subscription->custom_overage_price !== null
                ? (string) $subscription->custom_overage_price
                : '';
        } else {
            $this->subscriptionStatus = MerchantSubscriptionStatus::ACTIVE->value;
            $this->startsAt = now()->format('Y-m-d');
        }

        $this->showSubscriptionForm = true;
    }

    public function saveSubscription(): void
    {
        $validated = $this->validate([
            'editingMerchantId' => ['required', 'integer', 'exists:merchants,id'],
            'billingPlanId' => ['required', 'integer', 'exists:billing_plans,id'],

            'subscriptionStatus' => [
                'required',
                Rule::enum(MerchantSubscriptionStatus::class),
            ],

            'startsAt' => ['required', 'date'],
            'endsAt' => ['nullable', 'date', 'after_or_equal:startsAt'],

            'useCustomMonthlyFee' => ['boolean'],
            'useCustomPricePerMarket' => ['boolean'],
            'useCustomIncludedMarkets' => ['boolean'],
            'useCustomOveragePrice' => ['boolean'],

            'customMonthlyFee' => [
                Rule::requiredIf($this->useCustomMonthlyFee),
                'nullable',
                'numeric',
                'min:0',
            ],

            'customPricePerMarket' => [
                Rule::requiredIf($this->useCustomPricePerMarket),
                'nullable',
                'numeric',
                'min:0',
            ],

            'customIncludedMarkets' => [
                Rule::requiredIf($this->useCustomIncludedMarkets),
                'nullable',
                'integer',
                'min:0',
            ],

            'customOveragePrice' => [
                Rule::requiredIf($this->useCustomOveragePrice),
                'nullable',
                'numeric',
                'min:0',
            ],
        ]);

        $merchant = Merchant::findOrFail($this->editingMerchantId);
        $plan = BillingPlan::findOrFail((int) $validated['billingPlanId']);

        DB::transaction(function () use ($merchant, $plan, $validated) {

            $startsAt = Carbon::parse($validated['startsAt'])->startOfDay();

            $endsAt = $validated['endsAt']
                ? Carbon::parse($validated['endsAt'])->endOfDay()
                : null;

            $data = [
                'merchant_id' => $merchant->id,
                'billing_plan_id' => $plan->id,
                'status' => $validated['subscriptionStatus'],
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,

                'custom_monthly_fee' => $this->useCustomMonthlyFee
                    ? $validated['customMonthlyFee']
                    : null,

                'custom_price_per_market' => $this->useCustomPricePerMarket
                    ? $validated['customPricePerMarket']
                    : null,

                'custom_included_markets' => $this->useCustomIncludedMarkets
                    ? $validated['customIncludedMarkets']
                    : null,

                'custom_overage_price' => $this->useCustomOveragePrice
                    ? $validated['customOveragePrice']
                    : null,
            ];

            if ($this->editingSubscriptionId) {

                $currentSubscription = MerchantSubscription::query()
                    ->where('merchant_id', $merchant->id)
                    ->lockForUpdate()
                    ->findOrFail($this->editingSubscriptionId);

                /*
                 * IMPORTANT:
                 * A billing-plan change creates a NEW subscription.
                 *
                 * This preserves:
                 * - subscription history
                 * - billing-period history
                 * - market usage
                 * - historical prices
                 */
                if ((int) $currentSubscription->billing_plan_id !== (int) $plan->id) {

                    /*
                     * Close the previous subscription immediately before
                     * the new one starts.
                     */

                    if (
                        $currentSubscription->starts_at &&
                        $startsAt->lte($currentSubscription->starts_at)
                    ) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'startsAt' => 'The new billing plan must start after the current subscription starts.',
                        ]);
                    }

                    $oldEndsAt = $startsAt->copy()->subSecond();

                    $currentSubscription->update([
                        'status' => MerchantSubscriptionStatus::CANCELLED->value,
                        'ends_at' => $oldEndsAt,
                    ]);

                    /*
                     * The previous OPEN billing period must no longer
                     * accept markets from the new subscription.
                     */
                    MerchantBillingPeriod::query()
                        ->where('merchant_subscription_id', $currentSubscription->id)
                        ->where('status', MerchantBillingPeriodStatus::OPEN)
                        ->update([
                            'period_end' => $oldEndsAt,
                            'status' => MerchantBillingPeriodStatus::CLOSED->value,
                        ]);

                    /*
                     * Create a completely new subscription for the new plan.
                     */
                    MerchantSubscription::create($data);

                } else {

                    /*
                     * Same plan: only commercial/settings changes.
                     * Keep the same subscription.
                     */
                    $currentSubscription->update($data);
                }

            } else {

                MerchantSubscription::create($data);
            }

            $merchant->update([
                'is_paid' =>
                    $validated['subscriptionStatus']
                    === MerchantSubscriptionStatus::ACTIVE->value,
            ]);
        });

        session()->flash(
            'status',
            'Merchant billing settings saved successfully.'
        );

        $this->resetSubscriptionForm();
    }

    public function setSubscriptionStatus(int $merchantId, string $status): void
    {
        if (! in_array($status, array_column(MerchantSubscriptionStatus::cases(), 'value'), true)) {
            return;
        }

        $merchant = Merchant::query()
            ->with([
                'subscriptions' => fn ($query) => $query->latest('id'),
            ])
            ->findOrFail($merchantId);

        $subscription = $merchant->subscriptions->first();

        if (! $subscription) {
            session()->flash('error', 'This merchant does not have a billing subscription yet.');
            return;
        }

        DB::transaction(function () use ($merchant, $subscription, $status) {
            $subscription->update([
                'status' => $status,
            ]);

            $merchant->update([
                'is_paid' => $status === MerchantSubscriptionStatus::ACTIVE->value,
            ]);
        });

        session()->flash('status', 'Subscription status updated successfully.');
    }

    public function cancelSubscriptionForm(): void
    {
        $this->resetSubscriptionForm();
    }

    private function resetSubscriptionForm(): void
    {
        $this->showSubscriptionForm = false;
        $this->editingMerchantId = null;
        $this->editingSubscriptionId = null;

        $this->billingPlanId = '';
        $this->subscriptionStatus = MerchantSubscriptionStatus::ACTIVE->value;
        $this->startsAt = '';
        $this->endsAt = '';

        $this->customMonthlyFee = '';
        $this->customPricePerMarket = '';
        $this->customIncludedMarkets = '';
        $this->customOveragePrice = '';

        $this->useCustomMonthlyFee = false;
        $this->useCustomPricePerMarket = false;
        $this->useCustomIncludedMarkets = false;
        $this->useCustomOveragePrice = false;

        $this->resetValidation();
    }

    public function with(): array
    {
        $merchants = Merchant::query()
            ->with([
                'user',
                'wallets',
                'billingPeriods' => fn ($query) => $query->latest('period_start'),
                'subscriptions' => fn ($query) => $query
                    ->with('billingPlan')
                    ->latest('id'),
            ])
            ->when(
                trim($this->search) !== '',
                function ($query) {
                    $search = trim($this->search);

                    $query->where(function ($query) use ($search) {
                        $query
                            ->where('name', 'like', '%' . $search . '%')
                            ->orWhere('id', $search)
                            ->orWhereHas('user', function ($query) use ($search) {
                                $query
                                    ->where('name', 'like', '%' . $search . '%')
                                    ->orWhere('email', 'like', '%' . $search . '%');
                            });
                    });
                }
            )
            ->when(
                $this->statusFilter !== '',
                fn ($query) => $query->whereHas(
                    'subscriptions',
                    fn ($query) => $query->where('status', $this->statusFilter)
                )
            )
            ->when(
                $this->planFilter !== '',
                fn ($query) => $query->whereHas(
                    'subscriptions',
                    fn ($query) => $query->where('billing_plan_id', $this->planFilter)
                )
            )
            ->latest('id')
            ->paginate(20);

        return [
            'merchants' => $merchants,

            'billingPlans' => BillingPlan::query()
                ->orderByDesc('is_active')
                ->orderBy('name')
                ->get(),

            'subscriptionStatuses' => collect(MerchantSubscriptionStatus::cases())
                ->mapWithKeys(fn (MerchantSubscriptionStatus $status) => [
                    $status->value => $status->label(),
                ])
                ->toArray(),
        ];
    }
};
?>

<div class="space-y-6">

    {{-- HEADER --}}
    <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
        <div>
            <div class="text-xs font-black uppercase tracking-[0.18em] text-lime-500">
                Finance
            </div>

            <h1 class="mt-2 text-2xl font-black wr-text">
                Merchants
            </h1>

            <p class="mt-2 text-sm wr-muted">
                Manage merchant billing plans, subscription status and individual commercial conditions.
            </p>
        </div>

        <div class="grid w-full gap-3 sm:grid-cols-3 xl:w-auto">

            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="Merchant, user or email..."
                class="min-w-[260px] rounded-xl border px-4 py-3 text-sm outline-none wr-text"
                style="background: var(--wr-input); border-color: var(--wr-border);"
            >

            <select
                wire:model.live="planFilter"
                class="rounded-xl border px-4 py-3 text-sm outline-none wr-text"
                style="background: var(--wr-input); border-color: var(--wr-border);"
            >
                <option value="">All plans</option>

                @foreach($billingPlans as $plan)
                    <option value="{{ $plan->id }}">
                        {{ $plan->name }}
                    </option>
                @endforeach
            </select>

            <select
                wire:model.live="statusFilter"
                class="rounded-xl border px-4 py-3 text-sm outline-none wr-text"
                style="background: var(--wr-input); border-color: var(--wr-border);"
            >
                <option value="">All statuses</option>

                @foreach($subscriptionStatuses as $statusValue => $statusLabel)
                    <option value="{{ $statusValue }}">
                        {{ $statusLabel }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- FLASH --}}
    @if(session('status'))
        <div class="rounded-2xl border border-lime-500/20 bg-lime-500/10 px-5 py-4 text-sm font-bold text-lime-500">
            {{ session('status') }}
        </div>
    @endif

    @if(session('error'))
        <div class="rounded-2xl border border-red-500/20 bg-red-500/10 px-5 py-4 text-sm font-bold text-red-400">
            {{ session('error') }}
        </div>
    @endif

    {{-- SUBSCRIPTION FORM --}}
    @if($showSubscriptionForm)

        @php
            $selectedPlan = $billingPlans->firstWhere('id', (int) $billingPlanId);
        @endphp

        <div class="wr-panel rounded-2xl border p-6 sm:p-7">

            <div class="flex items-start justify-between gap-5">
                <div>
                    <div class="text-xs font-black uppercase tracking-[0.18em] text-lime-500">
                        Merchant Billing
                    </div>

                    <h2 class="mt-2 text-xl font-black wr-text">
                        {{ $editingSubscriptionId ? 'Edit Subscription' : 'Assign Billing Plan' }}
                    </h2>

                    <p class="mt-1 text-sm wr-muted">
                        Merchant ID #{{ $editingMerchantId }}
                    </p>
                </div>

                <button
                    type="button"
                    wire:click="cancelSubscriptionForm"
                    class="text-sm font-bold wr-muted transition hover:text-lime-500"
                >
                    Close
                </button>
            </div>

            <form wire:submit="saveSubscription" class="mt-7">

                <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">

                    {{-- PLAN --}}
                    <div>
                        <label class="mb-2 block text-sm font-bold wr-text-soft">
                            Billing Plan
                        </label>

                        <select
                            wire:model.live="billingPlanId"
                            class="w-full rounded-xl border px-4 py-3.5 outline-none wr-text"
                            style="background: var(--wr-input); border-color: var(--wr-border);"
                        >
                            <option value="">Select plan...</option>

                            @foreach($billingPlans as $plan)
                                <option value="{{ $plan->id }}">
                                    {{ $plan->name }}{{ $plan->is_active ? '' : ' вЂ” inactive' }}
                                </option>
                            @endforeach
                        </select>

                        @error('billingPlanId')
                        <div class="mt-2 text-xs font-bold text-red-400">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- STATUS --}}
                    <div>
                        <label class="mb-2 block text-sm font-bold wr-text-soft">
                            Status
                        </label>

                        <select
                            wire:model="subscriptionStatus"
                            class="w-full rounded-xl border px-4 py-3.5 outline-none wr-text"
                            style="background: var(--wr-input); border-color: var(--wr-border);"
                        >
                            @foreach($subscriptionStatuses as $statusValue => $statusLabel)
                                <option value="{{ $statusValue }}">
                                    {{ $statusLabel }}
                                </option>
                            @endforeach
                        </select>

                        @error('subscriptionStatus')
                        <div class="mt-2 text-xs font-bold text-red-400">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- START --}}
                    <div>
                        <label class="mb-2 block text-sm font-bold wr-text-soft">
                            Starts At
                        </label>

                        <input
                            type="date"
                            wire:model="startsAt"
                            class="w-full rounded-xl border px-4 py-3.5 outline-none wr-text"
                            style="background: var(--wr-input); border-color: var(--wr-border);"
                        >

                        @error('startsAt')
                        <div class="mt-2 text-xs font-bold text-red-400">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- END --}}
                    <div>
                        <label class="mb-2 block text-sm font-bold wr-text-soft">
                            Ends At
                        </label>

                        <input
                            type="date"
                            wire:model="endsAt"
                            class="w-full rounded-xl border px-4 py-3.5 outline-none wr-text"
                            style="background: var(--wr-input); border-color: var(--wr-border);"
                        >

                        <div class="mt-2 text-xs wr-muted">
                            Leave empty for no fixed end date.
                        </div>

                        @error('endsAt')
                        <div class="mt-2 text-xs font-bold text-red-400">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- BASE PLAN PREVIEW --}}
                @if($selectedPlan)
                    <div
                        class="mt-6 rounded-2xl border p-5"
                        style="border-color: var(--wr-border); background: var(--wr-panel-secondary);"
                    >
                        <div class="text-xs font-black uppercase tracking-[0.14em] wr-muted">
                            Base Plan Conditions
                        </div>

                        <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                            <div>
                                <div class="text-xs wr-muted">Type</div>
                                <div class="mt-1 font-bold wr-text">{{ $selectedPlan->billing_type->label() }}</div>
                            </div>

                            <div>
                                <div class="text-xs wr-muted">Monthly Fee</div>
                                <div class="mt-1 font-bold wr-text">{{ number_format((float) $selectedPlan->monthly_fee, 2) }}</div>
                            </div>

                            <div>
                                <div class="text-xs wr-muted">Per Market</div>
                                <div class="mt-1 font-bold wr-text">{{ number_format((float) $selectedPlan->price_per_market, 2) }}</div>
                            </div>

                            <div>
                                <div class="text-xs wr-muted">Included</div>
                                <div class="mt-1 font-bold wr-text">
                                    {{ $selectedPlan->is_unlimited ? 'Unlimited' : number_format($selectedPlan->included_markets) }}
                                </div>
                            </div>

                            <div>
                                <div class="text-xs wr-muted">Overage</div>
                                <div class="mt-1 font-bold wr-text">{{ number_format((float) $selectedPlan->overage_price, 2) }}</div>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- CUSTOM OVERRIDES --}}
                <div class="mt-7">
                    <div class="text-sm font-black wr-text">
                        Individual Conditions
                    </div>

                    <p class="mt-1 text-xs wr-muted">
                        Enable only the values that should override the selected billing plan.
                    </p>

                    <div class="mt-5 grid gap-5 md:grid-cols-2 xl:grid-cols-4">

                        {{-- MONTHLY --}}
                        <div class="rounded-xl border p-4" style="border-color: var(--wr-border);">
                            <label class="flex cursor-pointer items-center gap-3">
                                <input
                                    type="checkbox"
                                    wire:model.live="useCustomMonthlyFee"
                                    class="h-4 w-4 rounded"
                                >
                                <span class="text-sm font-bold wr-text">Custom Monthly Fee</span>
                            </label>

                            <input
                                type="number"
                                min="0"
                                step="0.01"
                                wire:model="customMonthlyFee"
                                @disabled(!$useCustomMonthlyFee)
                                placeholder="{{ $selectedPlan?->monthly_fee ?? '0.00' }}"
                                class="mt-4 w-full rounded-xl border px-4 py-3 outline-none wr-text disabled:cursor-not-allowed disabled:opacity-40"
                                style="background: var(--wr-input); border-color: var(--wr-border);"
                            >

                            @error('customMonthlyFee')
                            <div class="mt-2 text-xs font-bold text-red-400">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- PER MARKET --}}
                        <div class="rounded-xl border p-4" style="border-color: var(--wr-border);">
                            <label class="flex cursor-pointer items-center gap-3">
                                <input
                                    type="checkbox"
                                    wire:model.live="useCustomPricePerMarket"
                                    class="h-4 w-4 rounded"
                                >
                                <span class="text-sm font-bold wr-text">Custom Per Market</span>
                            </label>

                            <input
                                type="number"
                                min="0"
                                step="0.01"
                                wire:model="customPricePerMarket"
                                @disabled(!$useCustomPricePerMarket)
                                placeholder="{{ $selectedPlan?->price_per_market ?? '0.00' }}"
                                class="mt-4 w-full rounded-xl border px-4 py-3 outline-none wr-text disabled:cursor-not-allowed disabled:opacity-40"
                                style="background: var(--wr-input); border-color: var(--wr-border);"
                            >

                            @error('customPricePerMarket')
                            <div class="mt-2 text-xs font-bold text-red-400">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- INCLUDED --}}
                        <div class="rounded-xl border p-4" style="border-color: var(--wr-border);">
                            <label class="flex cursor-pointer items-center gap-3">
                                <input
                                    type="checkbox"
                                    wire:model.live="useCustomIncludedMarkets"
                                    class="h-4 w-4 rounded"
                                >
                                <span class="text-sm font-bold wr-text">Custom Included</span>
                            </label>

                            <input
                                type="number"
                                min="0"
                                step="1"
                                wire:model="customIncludedMarkets"
                                @disabled(!$useCustomIncludedMarkets)
                                placeholder="{{ $selectedPlan?->included_markets ?? '0' }}"
                                class="mt-4 w-full rounded-xl border px-4 py-3 outline-none wr-text disabled:cursor-not-allowed disabled:opacity-40"
                                style="background: var(--wr-input); border-color: var(--wr-border);"
                            >

                            @error('customIncludedMarkets')
                            <div class="mt-2 text-xs font-bold text-red-400">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- OVERAGE --}}
                        <div class="rounded-xl border p-4" style="border-color: var(--wr-border);">
                            <label class="flex cursor-pointer items-center gap-3">
                                <input
                                    type="checkbox"
                                    wire:model.live="useCustomOveragePrice"
                                    class="h-4 w-4 rounded"
                                >
                                <span class="text-sm font-bold wr-text">Custom Overage</span>
                            </label>

                            <input
                                type="number"
                                min="0"
                                step="0.01"
                                wire:model="customOveragePrice"
                                @disabled(!$useCustomOveragePrice)
                                placeholder="{{ $selectedPlan?->overage_price ?? '0.00' }}"
                                class="mt-4 w-full rounded-xl border px-4 py-3 outline-none wr-text disabled:cursor-not-allowed disabled:opacity-40"
                                style="background: var(--wr-input); border-color: var(--wr-border);"
                            >

                            @error('customOveragePrice')
                            <div class="mt-2 text-xs font-bold text-red-400">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- ACTIONS --}}
                <div class="mt-7 flex flex-wrap gap-3">
                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        wire:target="saveSubscription"
                        class="rounded-xl bg-lime-400 px-6 py-3.5 font-black text-[#06111f] transition hover:bg-lime-300 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <span wire:loading.remove wire:target="saveSubscription">
                            Save Billing Settings
                        </span>

                        <span wire:loading wire:target="saveSubscription">
                            Saving...
                        </span>
                    </button>

                    <button
                        type="button"
                        wire:click="cancelSubscriptionForm"
                        class="rounded-xl border px-6 py-3.5 text-sm font-bold wr-text transition hover:bg-[var(--wr-hover)]"
                        style="border-color: var(--wr-border);"
                    >
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    @endif

    {{-- TABLE --}}
    <div class="wr-panel overflow-hidden rounded-xl border">

        <div class="overflow-x-auto">
            <table class="min-w-[1550px] w-full">

                <thead style="background: var(--wr-panel-secondary);">
                <tr class="border-b" style="border-color: var(--wr-border);">
                    <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wider wr-muted">Merchant</th>
                    <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wider wr-muted">Owner</th>
                    <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wider wr-muted">Plan</th>
                    <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wider wr-muted">Subscription</th>
                    <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wider wr-muted">Automation</th>
                    <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wider wr-muted">Billing Status</th>
                    <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wider wr-muted">Period</th>
                    <th class="px-5 py-4 text-right text-xs font-bold uppercase tracking-wider wr-muted">Balance</th>
                    <th class="px-5 py-4 text-center text-xs font-bold uppercase tracking-wider wr-muted">Paid</th>
                    <th class="px-5 py-4 text-right text-xs font-bold uppercase tracking-wider wr-muted">Actions</th>
                </tr>
                </thead>

                <tbody>
                @forelse($merchants as $merchant)

                    @php
                        $subscription = $merchant->subscriptions->first();
                        $wallets = $merchant->wallets;
                        $usdWallet = $wallets->firstWhere('currency', 'USD');
                        $usdBalance = round((float) ($usdWallet?->balance ?? 0), 2);
                        $changeReason = $subscription?->change_reason?->value;

                        $fallbackSource = null;
                        if ($subscription?->fallback_from_subscription_id) {
                            $fallbackSource = $merchant->subscriptions->firstWhere(
                                'id',
                                (int) $subscription->fallback_from_subscription_id
                            );
                        }

                        $currentPeriod = $subscription
                            ? $merchant->billingPeriods->first(
                                fn ($period) =>
                                    (int) $period->merchant_subscription_id === (int) $subscription->id
                            )
                            : null;

                        $restoreMonthlyFee = null;
                        $restoreMissingAmount = null;

                        if (
                            $subscription
                            && $changeReason === MerchantSubscriptionChangeReason::INSUFFICIENT_BALANCE->value
                            && $fallbackSource?->billingPlan
                        ) {
                            $restoreMonthlyFee = round(
                                (float) (
                                    $fallbackSource->custom_monthly_fee
                                    ?? $fallbackSource->billingPlan->monthly_fee
                                    ?? 0
                                ),
                                2
                            );

                            $restoreMissingAmount = max(
                                0,
                                round($restoreMonthlyFee - $usdBalance, 2)
                            );
                        }

                        $previousFallback = null;
                        if (
                            $subscription
                            && $changeReason === MerchantSubscriptionChangeReason::BALANCE_RESTORED->value
                        ) {
                            $previousFallback = $merchant->subscriptions->first(
                                fn ($item) =>
                                    (int) $item->id !== (int) $subscription->id
                                    && $item->change_reason?->value
                                        === MerchantSubscriptionChangeReason::INSUFFICIENT_BALANCE->value
                            );
                        }
                    @endphp

                    <tr
                        wire:key="finance-merchant-{{ $merchant->id }}"
                        class="border-b transition hover:bg-[var(--wr-hover)]"
                        style="border-color: var(--wr-border-soft);"
                    >
                        <td class="px-5 py-5">
                            <div class="font-black wr-text">
                                {{ $merchant->name }}
                            </div>

                            <div class="mt-1 text-xs wr-muted">
                                ID #{{ $merchant->id }}
                            </div>
                        </td>

                        <td class="px-5 py-5">
                            <div class="text-sm font-semibold wr-text-soft">
                                {{ $merchant->user?->name ?: 'вЂ”' }}
                            </div>

                            <div class="mt-1 text-xs wr-muted">
                                {{ $merchant->user?->email ?: 'No user' }}
                            </div>
                        </td>

                        <td class="px-5 py-5">
                            @if($subscription?->billingPlan)
                                <div class="font-bold wr-text">
                                    {{ $subscription->billingPlan->name }}
                                </div>

                                <div class="mt-1 text-xs wr-muted">
                                    {{ $subscription->billingPlan->billing_type->label() }}
                                </div>

                                @if(
                                    $subscription->custom_monthly_fee !== null ||
                                    $subscription->custom_price_per_market !== null ||
                                    $subscription->custom_included_markets !== null ||
                                    $subscription->custom_overage_price !== null
                                )
                                    <div class="mt-2 text-xs font-bold text-amber-400">
                                        Custom conditions
                                    </div>
                                @endif
                            @else
                                <span class="text-sm wr-muted">No plan</span>
                            @endif
                        </td>

                        <td class="px-5 py-5">
                            @if($subscription)

                                @if($changeReason === 'insufficient_balance')

                                    <div class="inline-flex items-center gap-2 rounded-full bg-amber-500/10 px-3 py-1.5 text-xs font-black text-amber-400">
                                        <span class="h-2 w-2 rounded-full bg-amber-400"></span>
                                        Automatic fallback
                                    </div>

                                    <div class="mt-2 max-w-[220px] text-xs leading-5 text-amber-400/80">
                                        Switched to {{ $subscription->billingPlan?->name ?? 'default plan' }}
                                        because of insufficient balance.
                                    </div>

                                @elseif($changeReason === MerchantSubscriptionChangeReason::BALANCE_RESTORED->value)

                                    <div class="inline-flex items-center gap-2 rounded-full bg-lime-500/10 px-3 py-1.5 text-xs font-black text-lime-500">
                                        <span class="h-2 w-2 rounded-full bg-lime-500"></span>
                                        Restored automatically
                                    </div>

                                    <div class="mt-2 max-w-[220px] text-xs leading-5 wr-muted">
                                        Previous recurring plan restored after sufficient balance became available.
                                    </div>

                                @elseif($changeReason === 'merchant_created')

                                    <div class="inline-flex items-center gap-2 rounded-full bg-sky-500/10 px-3 py-1.5 text-xs font-black text-sky-400">
                                        <span class="h-2 w-2 rounded-full bg-sky-400"></span>
                                        Default plan
                                    </div>

                                    <div class="mt-2 text-xs wr-muted">
                                        Assigned automatically when merchant was created.
                                    </div>

                                @else

                                    <div class="inline-flex items-center gap-2 rounded-full bg-lime-500/10 px-3 py-1.5 text-xs font-black text-lime-500">
                                        <span class="h-2 w-2 rounded-full bg-lime-500"></span>
                                        Normal
                                    </div>

                                    <div class="mt-2 text-xs wr-muted">
                                        Billing plan assigned normally.
                                    </div>

                                @endif

                            @else
                                <span class="text-sm wr-muted">вЂ”</span>
                            @endif
                        </td>

                        {{-- AUTOMATION --}}
                        <td class="px-5 py-5">
                            @if(
                                $subscription
                                && $changeReason === MerchantSubscriptionChangeReason::INSUFFICIENT_BALANCE->value
                            )
                                @if($fallbackSource?->billingPlan)
                                    <div class="text-xs font-black uppercase tracking-[0.12em] wr-muted">
                                        Previous Plan
                                    </div>
                                    <div class="mt-1 font-bold wr-text">
                                        {{ $fallbackSource->billingPlan->name }}
                                    </div>
                                    <div class="mt-1 text-xs wr-muted">
                                        {{ $fallbackSource->billingPlan->billing_type->label() }}
                                    </div>

                                    <div class="mt-3 rounded-xl border p-3"
                                         style="border-color: var(--wr-border); background: var(--wr-panel-secondary);">
                                        <div class="flex justify-between gap-4 text-xs">
                                            <span class="wr-muted">Required</span>
                                            <span class="font-black wr-text">${{ number_format((float) $restoreMonthlyFee, 2) }}</span>
                                        </div>
                                        <div class="mt-2 flex justify-between gap-4 text-xs">
                                            <span class="wr-muted">Balance</span>
                                            <span class="font-black wr-text">${{ number_format($usdBalance, 2) }}</span>
                                        </div>
                                        <div class="mt-2 flex justify-between gap-4 text-xs">
                                            <span class="wr-muted">Still needed</span>
                                            <span class="font-black {{ $restoreMissingAmount > 0 ? 'text-amber-400' : 'text-lime-500' }}">
                                                ${{ number_format((float) $restoreMissingAmount, 2) }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="mt-3 max-w-[250px] text-xs font-semibold leading-5 text-amber-400">
                                        в†» Will restore automatically when USD balance reaches
                                        ${{ number_format((float) $restoreMonthlyFee, 2) }}.
                                    </div>
                                @else
                                    <div class="text-xs font-bold text-red-400">
                                        Fallback source unavailable.
                                    </div>
                                @endif

                            @elseif(
                                $subscription
                                && $changeReason === MerchantSubscriptionChangeReason::BALANCE_RESTORED->value
                            )
                                <div class="text-xs font-black uppercase tracking-[0.12em] text-lime-500">
                                    Restore completed
                                </div>

                                @if($previousFallback?->billingPlan)
                                    <div class="mt-2 text-xs wr-muted">Previous fallback</div>
                                    <div class="mt-1 font-bold wr-text">
                                        {{ $previousFallback->billingPlan->name }}
                                    </div>
                                @endif

                                @if($currentPeriod)
                                    <div class="mt-3 rounded-xl border p-3"
                                         style="border-color: var(--wr-border); background: var(--wr-panel-secondary);">
                                        <div class="flex justify-between gap-4 text-xs">
                                            <span class="wr-muted">Monthly fee</span>
                                            <span class="font-black wr-text">
                                                ${{ number_format((float) $currentPeriod->monthly_fee, 2) }}
                                            </span>
                                        </div>
                                        <div class="mt-2 flex justify-between gap-4 text-xs">
                                            <span class="wr-muted">Period ends</span>
                                            <span class="font-bold wr-text">
                                                {{ $currentPeriod->period_end?->format('Y-m-d') ?: 'вЂ”' }}
                                            </span>
                                        </div>
                                    </div>
                                @endif
                            @else
                                <span class="text-xs wr-muted">No automatic transition.</span>
                            @endif
                        </td>

                        <td class="px-5 py-5">
                            @if($subscription)
                                @php
                                    $statusValue = $subscription->status->value;
                                    $statusClass = match($statusValue) {
                                        'active' => 'bg-lime-500/10 text-lime-500',
                                        'suspended' => 'bg-amber-500/10 text-amber-400',
                                        'cancelled' => 'bg-red-500/10 text-red-400',
                                        'expired' => 'bg-slate-500/10 wr-muted',
                                        default => 'bg-slate-500/10 wr-muted',
                                    };
                                @endphp

                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-black {{ $statusClass }}">
                                        {{ $subscription->status->label() }}
                                    </span>
                            @else
                                <span class="text-sm wr-muted">Not assigned</span>
                            @endif
                        </td>

                        <td class="px-5 py-5">
                            @if($currentPeriod)
                                <div class="text-sm font-semibold wr-text-soft">
                                    {{ $currentPeriod->period_start?->format('Y-m-d') ?: 'вЂ”' }}
                                </div>
                                <div class="mt-1 text-xs wr-muted">
                                    to {{ $currentPeriod->period_end?->format('Y-m-d') ?: 'No end date' }}
                                </div>
                                <div class="mt-2 text-xs">
                                    @if($currentPeriod->monthly_fee_charged_at)
                                        <span class="font-bold text-lime-500">Monthly fee processed</span>
                                    @elseif((float) $currentPeriod->monthly_fee > 0)
                                        <span class="font-bold text-amber-400">Monthly fee pending</span>
                                    @else
                                        <span class="wr-muted">No recurring fee</span>
                                    @endif
                                </div>
                            @elseif($subscription)
                                <div class="text-sm wr-text-soft">
                                    {{ $subscription->starts_at?->format('Y-m-d') ?: 'вЂ”' }}
                                </div>
                                <div class="mt-1 text-xs wr-muted">Subscription start</div>
                            @else
                                <span class="wr-muted">вЂ”</span>
                            @endif
                        </td>

                        <td class="px-5 py-5 text-right">
                            @forelse($wallets as $wallet)
                                <div class="text-sm font-bold wr-text">
                                    {{ number_format((float) $wallet->balance, 2) }}
                                    <span class="text-xs wr-muted">{{ $wallet->currency }}</span>
                                </div>
                            @empty
                                <span class="text-sm wr-muted">No wallet</span>
                            @endforelse
                        </td>

                        <td class="px-5 py-5 text-center">
                            @if($merchant->is_paid)
                                <span class="inline-flex rounded-full bg-lime-500/10 px-3 py-1 text-xs font-black text-lime-500">
                                        Yes
                                    </span>
                            @else
                                <span class="inline-flex rounded-full bg-slate-500/10 px-3 py-1 text-xs font-black wr-muted">
                                        No
                                    </span>
                            @endif
                        </td>

                        <td class="px-5 py-5">
                            <div class="flex justify-end gap-2">

                                <button
                                    type="button"
                                    wire:click="manageSubscription({{ $merchant->id }})"
                                    class="rounded-lg border px-3 py-2 text-xs font-bold wr-text transition hover:bg-[var(--wr-hover)]"
                                    style="border-color: var(--wr-border);"
                                >
                                    {{ $subscription ? 'Billing Settings' : 'Assign Plan' }}
                                </button>

                                @if($subscription && $subscription->status === \App\Enums\MerchantSubscriptionStatus::ACTIVE)
                                    <button
                                        type="button"
                                        wire:click="setSubscriptionStatus({{ $merchant->id }}, 'suspended')"
                                        wire:confirm="Suspend billing for {{ $merchant->name }}?"
                                        class="rounded-lg border border-amber-500/20 px-3 py-2 text-xs font-bold text-amber-400 transition hover:bg-amber-500/10"
                                    >
                                        Suspend
                                    </button>
                                @elseif($subscription && $subscription->status === \App\Enums\MerchantSubscriptionStatus::SUSPENDED)
                                    <button
                                        type="button"
                                        wire:click="setSubscriptionStatus({{ $merchant->id }}, 'active')"
                                        wire:confirm="Reactivate billing for {{ $merchant->name }}?"
                                        class="rounded-lg border border-lime-500/20 px-3 py-2 text-xs font-bold text-lime-500 transition hover:bg-lime-500/10"
                                    >
                                        Activate
                                    </button>
                                @endif

                            </div>
                        </td>
                    </tr>

                @empty
                    <tr>
                        <td colspan="10" class="px-6 py-16 text-center">
                            <div class="text-base font-semibold wr-text">
                                No merchants found
                            </div>

                            <div class="mt-2 text-sm wr-muted">
                                Try changing the search or filters.
                            </div>

                            @if($search !== '' || $statusFilter !== '' || $planFilter !== '')
                                <button
                                    type="button"
                                    wire:click="clearFilters"
                                    class="mt-4 text-sm font-black text-lime-500 hover:text-lime-400"
                                >
                                    Clear filters
                                </button>
                            @endif
                        </td>
                    </tr>
                @endforelse
                </tbody>

            </table>
        </div>

        @if($merchants->hasPages())
            <div class="border-t px-5 py-4" style="border-color: var(--wr-border);">
                {{ $merchants->links() }}
            </div>
        @endif
    </div>

</div>
