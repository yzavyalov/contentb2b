<?php

use App\Enums\BillingType;
use App\Models\BillingPlan;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

new class extends Component
{
    use WithPagination;

    public bool $showCreatePlan = false;
    public ?int $editingPlanId = null;

    public string $name = '';
    public string $billingType = 'per_market';

    public string $monthlyFee = '0.00';
    public string $pricePerMarket = '0.00';
    public string $includedMarkets = '0';
    public bool $isUnlimited = false;
    public string $overagePrice = '0.00';

    public bool $isActive = true;

    public function createPlanForm(): void
    {
        $this->resetPlanForm();
        $this->showCreatePlan = true;
    }

    public function editPlan(int $planId): void
    {
        $plan = BillingPlan::findOrFail($planId);

        $this->resetValidation();

        $this->editingPlanId = $plan->id;
        $this->name = $plan->name;
        $this->billingType = $plan->billing_type instanceof BillingType
            ? $plan->billing_type->value
            : (string) $plan->billing_type;
        $this->monthlyFee = (string) $plan->monthly_fee;
        $this->pricePerMarket = (string) $plan->price_per_market;
        $this->includedMarkets = (string) $plan->included_markets;
        $this->isUnlimited = (bool) $plan->is_unlimited;
        $this->overagePrice = (string) $plan->overage_price;
        $this->isActive = (bool) $plan->is_active;

        $this->showCreatePlan = true;
    }

    public function cancelCreatePlan(): void
    {
        $this->resetPlanForm();
    }

    public function savePlan(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'billingType' => ['required', 'in:per_market,subscription,hybrid'],
            'monthlyFee' => ['required', 'numeric', 'min:0'],
            'pricePerMarket' => ['required', 'numeric', 'min:0'],
            'includedMarkets' => ['required', 'integer', 'min:0'],
            'overagePrice' => ['required', 'numeric', 'min:0'],
            'isUnlimited' => ['boolean'],
            'isActive' => ['boolean'],
        ]);

        $data = [
            'name' => trim($validated['name']),
            'billing_type' => $validated['billingType'],
            'monthly_fee' => $validated['monthlyFee'],
            'price_per_market' => $validated['pricePerMarket'],
            'included_markets' => $validated['isUnlimited'] ? 0 : $validated['includedMarkets'],
            'is_unlimited' => $validated['isUnlimited'],
            'overage_price' => $validated['overagePrice'],
            'is_active' => $validated['isActive'],
        ];

        if ($this->editingPlanId) {
            $plan = BillingPlan::findOrFail($this->editingPlanId);

            if ($plan->is_default && ! $validated['isActive']) {
                throw ValidationException::withMessages([
                    'isActive' => 'The default billing plan cannot be deactivated. Assign another default plan first.',
                ]);
            }

            $plan->update($data);
            session()->flash('status', 'Billing plan updated successfully.');
        } else {
            BillingPlan::create($data);
            session()->flash('status', 'Billing plan created successfully.');
        }

        $this->resetPlanForm();
        $this->resetPage();
    }

    public function togglePlanStatus(int $planId): void
    {
        $plan = BillingPlan::findOrFail($planId);

        if ($plan->is_default && $plan->is_active) {
            session()->flash('error', 'The default billing plan cannot be deactivated. Assign another default plan first.');
            return;
        }

        $plan->update(['is_active' => ! $plan->is_active]);

        session()->flash(
            'status',
            $plan->is_active
                ? 'Billing plan activated successfully.'
                : 'Billing plan deactivated successfully.'
        );
    }

    public function makeDefaultPlan(int $planId): void
    {
        DB::transaction(function () use ($planId) {
            $plan = BillingPlan::query()->lockForUpdate()->findOrFail($planId);

            if (! $plan->is_active) {
                throw ValidationException::withMessages([
                    'defaultPlan' => 'Only an active billing plan can be set as default.',
                ]);
            }

            BillingPlan::query()->where('is_default', true)->lockForUpdate()->get();

            BillingPlan::query()
                ->where('id', '!=', $plan->id)
                ->where('is_default', true)
                ->update(['is_default' => false]);

            if (! $plan->is_default) {
                $plan->update(['is_default' => true]);
            }
        }, 3);

        session()->flash('status', 'Default billing plan updated successfully.');
    }

    public function updatedBillingType(string $value): void
    {
        if ($value === BillingType::PER_MARKET->value) {
            $this->monthlyFee = '0.00';
            $this->includedMarkets = '0';
            $this->isUnlimited = false;
            $this->overagePrice = '0.00';
        }

        if ($value === BillingType::SUBSCRIPTION->value) {
            $this->pricePerMarket = '0.00';
        }
    }

    public function updatedIsUnlimited(bool $value): void
    {
        if ($value) {
            $this->includedMarkets = '0';
        }
    }

    private function resetPlanForm(): void
    {
        $this->showCreatePlan = false;
        $this->editingPlanId = null;
        $this->name = '';
        $this->billingType = BillingType::PER_MARKET->value;
        $this->monthlyFee = '0.00';
        $this->pricePerMarket = '0.00';
        $this->includedMarkets = '0';
        $this->isUnlimited = false;
        $this->overagePrice = '0.00';
        $this->isActive = true;
        $this->resetValidation();
    }

    public function with(): array
    {
        return [
            'plans' => BillingPlan::query()
                ->latest('id')
                ->paginate(20),
        ];
    }
};
?>

<div class="space-y-6">

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

        <div>
            <h1 class="text-2xl font-semibold wr-text">
                Billing Plans
            </h1>

            <p class="mt-1 text-sm wr-muted">
                Manage pricing models and billing conditions for merchants.
            </p>
        </div>

        <button
            type="button"
            wire:click="createPlanForm"
            class="rounded-xl bg-lime-400 px-5 py-3 text-sm font-black text-[#06111f] transition hover:bg-lime-300"
        >
            Create Plan
        </button>

    </div>

    @if(session('status'))
        <div class="rounded-2xl border border-lime-500/20 bg-lime-500/10 px-5 py-4 text-sm font-bold text-lime-500">{{ session('status') }}</div>
    @endif

    @if(session('error'))
        <div class="rounded-2xl border border-red-500/20 bg-red-500/10 px-5 py-4 text-sm font-bold text-red-400">{{ session('error') }}</div>
    @endif

    @error('defaultPlan')
    <div class="rounded-2xl border border-red-500/20 bg-red-500/10 px-5 py-4 text-sm font-bold text-red-400">{{ $message }}</div>
    @enderror

    <div class="wr-panel overflow-hidden rounded-xl border">

        @if($showCreatePlan)

            <div class="wr-panel rounded-2xl border p-6 sm:p-7">

                <div class="flex items-start justify-between gap-5">

                    <div>
                        <div class="text-xs font-black uppercase tracking-[0.18em] text-lime-500">
                            {{ $editingPlanId ? 'Edit Billing Plan' : 'New Billing Plan' }}
                        </div>

                        <h2 class="mt-2 text-xl font-black wr-text">
                            Create Plan
                        </h2>

                        <p class="mt-1 text-sm wr-muted">
                            Configure pricing and usage limits for this merchant plan.
                        </p>
                    </div>

                    <button
                        type="button"
                        wire:click="cancelCreatePlan"
                        class="text-sm font-bold wr-muted transition hover:text-lime-500"
                    >
                        Close
                    </button>

                </div>

                <div class="mt-7 grid gap-5 md:grid-cols-2 xl:grid-cols-3">

                    {{-- NAME --}}
                    <div>
                        <label class="mb-2 block text-sm font-bold wr-text-soft">
                            Plan Name
                        </label>

                        <input
                            type="text"
                            wire:model="name"
                            placeholder="Standard"
                            class="w-full rounded-xl border px-4 py-3.5 outline-none wr-text"
                            style="
                        background: var(--wr-input);
                        border-color: var(--wr-border);
                    "
                        >
                    </div>

                    {{-- BILLING TYPE --}}
                    <div>
                        <label class="mb-2 block text-sm font-bold wr-text-soft">
                            Billing Type
                        </label>

                        <select
                            wire:model.live="billingType"
                            class="w-full rounded-xl border px-4 py-3.5 outline-none wr-text"
                            style="
                        background: var(--wr-input);
                        border-color: var(--wr-border);
                    "
                        >
                            <option value="per_market">Per Market</option>
                            <option value="subscription">Subscription</option>
                            <option value="hybrid">Hybrid</option>
                        </select>
                    </div>

                    {{-- MONTHLY FEE --}}
                    <div>
                        <label class="mb-2 block text-sm font-bold wr-text-soft">
                            Monthly Fee
                        </label>

                        <input
                            type="number"
                            min="0"
                            step="0.01"
                            wire:model="monthlyFee"
                            @disabled($billingType === 'per_market')
                            class="w-full rounded-xl border px-4 py-3.5 outline-none wr-text"
                            style="
                        background: var(--wr-input);
                        border-color: var(--wr-border);
                    "
                        >
                    </div>

                    {{-- PRICE PER MARKET --}}
                    <div>
                        <label class="mb-2 block text-sm font-bold wr-text-soft">
                            Price per Market
                        </label>

                        <input
                            type="number"
                            min="0"
                            step="0.01"
                            wire:model="pricePerMarket"
                            @disabled($billingType === 'subscription')
                            class="w-full rounded-xl border px-4 py-3.5 outline-none wr-text"
                            style="
                        background: var(--wr-input);
                        border-color: var(--wr-border);
                    "
                        >
                    </div>

                    {{-- INCLUDED MARKETS --}}
                    <div>
                        <label class="mb-2 block text-sm font-bold wr-text-soft">
                            Included Markets
                        </label>

                        <input
                            type="number"
                            min="0"
                            step="1"
                            wire:model="includedMarkets"
                            @disabled($isUnlimited || $billingType === 'per_market')
                            class="w-full rounded-xl border px-4 py-3.5 outline-none wr-text disabled:cursor-not-allowed disabled:opacity-40"
                            style="
                        background: var(--wr-input);
                        border-color: var(--wr-border);
                    "
                        >
                    </div>

                    {{-- OVERAGE --}}
                    <div>
                        <label class="mb-2 block text-sm font-bold wr-text-soft">
                            Overage Price
                        </label>

                        <input
                            type="number"
                            min="0"
                            step="0.01"
                            wire:model="overagePrice"
                            @disabled($isUnlimited || $billingType === 'per_market')
                            class="w-full rounded-xl border px-4 py-3.5 outline-none wr-text"
                            style="
                        background: var(--wr-input);
                        border-color: var(--wr-border);
                    "
                        >
                    </div>

                </div>

                {{-- OPTIONS --}}
                <div
                    class="mt-6 flex flex-col gap-4 rounded-xl border p-5 sm:flex-row sm:gap-8"
                    style="border-color: var(--wr-border);"
                >

                    <label class="flex cursor-pointer items-center gap-3">

                        <input
                            type="checkbox"
                            wire:model.live="isUnlimited"
                            @disabled($billingType === 'per_market')
                            class="h-4 w-4 rounded"
                        >

                        <span class="text-sm font-semibold wr-text">
                    Unlimited markets
                </span>

                    </label>

                    <label class="flex cursor-pointer items-center gap-3">

                        <input
                            type="checkbox"
                            wire:model="isActive"
                            class="h-4 w-4 rounded"
                        >

                        <span class="text-sm font-semibold wr-text">
                    Active plan
                </span>

                    </label>

                </div>

                {{-- ACTION --}}
                <div class="mt-6">

                    <button
                        type="button"
                        wire:click="savePlan"
                        wire:loading.attr="disabled"
                        wire:target="savePlan"
                        class="
                                    rounded-xl
                                    bg-lime-400
                                    px-6 py-3.5
                                    font-black
                                    text-[#06111f]
                                    transition
                                    hover:bg-lime-300
                                    disabled:cursor-not-allowed
                                    disabled:opacity-50
                                "
                    >
                    <span wire:loading.remove wire:target="savePlan">
                        {{ $editingPlanId ? 'Save Changes' : 'Create Billing Plan' }}
                    </span>

                        <span wire:loading wire:target="savePlan">
                            Saving...
                        </span>
                    </button>

                </div>

            </div>

        @endif

        <div class="overflow-x-auto">

            <table class="min-w-full">

                <thead style="background: var(--wr-panel-secondary);">
                <tr class="border-b" style="border-color: var(--wr-border);">

                    <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wider wr-muted">
                        Plan
                    </th>

                    <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wider wr-muted">
                        Type
                    </th>

                    <th class="px-5 py-4 text-right text-xs font-bold uppercase tracking-wider wr-muted">
                        Monthly Fee
                    </th>

                    <th class="px-5 py-4 text-right text-xs font-bold uppercase tracking-wider wr-muted">
                        Per Market
                    </th>

                    <th class="px-5 py-4 text-right text-xs font-bold uppercase tracking-wider wr-muted">
                        Included
                    </th>

                    <th class="px-5 py-4 text-right text-xs font-bold uppercase tracking-wider wr-muted">
                        Overage
                    </th>

                    <th class="px-5 py-4 text-center text-xs font-bold uppercase tracking-wider wr-muted">
                        Status
                    </th>

                    <th class="px-5 py-4 text-center text-xs font-bold uppercase tracking-wider wr-muted">Default</th>
                    <th class="px-5 py-4"></th>

                </tr>
                </thead>

                <tbody>

                @forelse($plans as $plan)

                    <tr
                        wire:key="billing-plan-{{ $plan->id }}"
                        class="border-b"
                        style="border-color: var(--wr-border-soft);"
                    >

                        <td class="px-5 py-4">
                            <div class="font-semibold wr-text">
                                {{ $plan->name }}
                            </div>

                            <div class="mt-1 text-xs wr-muted">
                                ID #{{ $plan->id }}
                            </div>
                        </td>

                        <td class="px-5 py-4 text-sm wr-text-soft">
                            {{ $plan->billing_type->label() }}
                        </td>

                        <td class="px-5 py-4 text-right text-sm wr-text">
                            ${{ number_format((float) $plan->monthly_fee, 2) }}
                        </td>

                        <td class="px-5 py-4 text-right text-sm wr-text">
                            ${{ number_format((float) $plan->price_per_market, 2) }}
                        </td>

                        <td class="px-5 py-4 text-right text-sm wr-text">

                            @if($plan->is_unlimited)
                                <span class="font-semibold text-lime-500">
                                        Unlimited
                                    </span>
                            @else
                                {{ number_format($plan->included_markets) }}
                            @endif

                        </td>

                        <td class="px-5 py-4 text-right text-sm wr-text">
                            ${{ number_format((float) $plan->overage_price, 2) }}
                        </td>

                        <td class="px-5 py-4 text-center">

                            @if($plan->is_active)

                                <span class="inline-flex rounded-full bg-lime-500/10 px-3 py-1 text-xs font-bold text-lime-500">
                                        Active
                                    </span>

                            @else

                                <span class="inline-flex rounded-full bg-slate-500/10 px-3 py-1 text-xs font-bold wr-muted">
                                        Inactive
                                    </span>

                            @endif

                        </td>

                        <td class="px-5 py-4 text-center">
                            @if($plan->is_default)
                                <span class="inline-flex items-center gap-2 rounded-full bg-sky-500/10 px-3 py-1 text-xs font-black text-sky-500">
                                    <span class="h-2 w-2 rounded-full bg-sky-500"></span> Default
                                </span>
                            @else
                                <span class="text-xs wr-muted">вЂ”</span>
                            @endif
                        </td>

                        <td class="px-5 py-4 text-right">

                            @if(! $plan->is_default && $plan->is_active)
                                <button type="button" wire:click="makeDefaultPlan({{ $plan->id }})" wire:confirm="Make {{ $plan->name }} the default billing plan? The current default plan will be replaced." class="mr-2 rounded-lg border border-sky-500/20 px-3 py-2 text-xs font-bold text-sky-500 transition hover:bg-sky-500/10">Make Default</button>
                            @elseif($plan->is_default)
                                <span class="mr-2 inline-flex rounded-lg bg-sky-500/10 px-3 py-2 text-xs font-black text-sky-500">Current Default</span>
                            @endif

                            <button
                                type="button"
                                wire:click="editPlan({{ $plan->id }})"
                                class="rounded-lg border px-3 py-2 text-xs font-bold wr-text transition hover:bg-[var(--wr-hover)]"
                                style="border-color: var(--wr-border);"
                            >
                                Edit
                            </button>

                            <button
                                type="button"
                                wire:click="togglePlanStatus({{ $plan->id }})"
                                wire:confirm="{{ $plan->is_active ? 'Deactivate this billing plan?' : 'Activate this billing plan?' }}"
                                class="ml-2 rounded-lg border px-3 py-2 text-xs font-bold transition {{ $plan->is_active ? 'border-amber-500/20 text-amber-400 hover:bg-amber-500/10' : 'border-lime-500/20 text-lime-500 hover:bg-lime-500/10' }}"
                            >
                                {{ $plan->is_active ? 'Deactivate' : 'Activate' }}
                            </button>

                        </td>

                    </tr>

                @empty

                    <tr>
                        <td colspan="9" class="px-6 py-16 text-center">

                            <div class="text-base font-semibold wr-text">
                                No billing plans
                            </div>

                            <div class="mt-2 text-sm wr-muted">
                                Create your first merchant billing plan.
                            </div>

                        </td>
                    </tr>

                @endforelse

                </tbody>

            </table>

        </div>

        @if($plans->hasPages())
            <div
                class="border-t px-5 py-4"
                style="border-color: var(--wr-border);"
            >
                {{ $plans->links() }}
            </div>
        @endif

    </div>

</div>
