<?php

use App\Enums\MerchantBalanceTransactionType;
use App\Models\Merchant;
use App\Models\MerchantBalanceTransaction;
use App\Models\MerchantWallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $search = '';
    public string $merchantFilter = '';
    public string $typeFilter = '';
    public string $currencyFilter = '';
    public string $dateFrom = '';
    public string $dateTo = '';

    public bool $showAdjustmentForm = false;
    public string $adjustmentMerchantId = '';
    public string $adjustmentType = 'manual_credit';
    public string $adjustmentAmount = '';
    public string $adjustmentCurrency = 'USD';
    public string $adjustmentDescription = '';

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingMerchantFilter(): void { $this->resetPage(); }
    public function updatingTypeFilter(): void { $this->resetPage(); }
    public function updatingCurrencyFilter(): void { $this->resetPage(); }
    public function updatingDateFrom(): void { $this->resetPage(); }
    public function updatingDateTo(): void { $this->resetPage(); }

    public function clearFilters(): void
    {
        $this->reset(['search', 'merchantFilter', 'typeFilter', 'currencyFilter', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    public function openAdjustmentForm(): void
    {
        $this->resetAdjustmentForm();
        $this->showAdjustmentForm = true;
    }

    public function cancelAdjustmentForm(): void
    {
        $this->resetAdjustmentForm();
    }

    public function saveAdjustment(): void
    {
        $validated = $this->validate([
            'adjustmentMerchantId' => ['required', 'integer', 'exists:merchants,id'],
            'adjustmentType' => ['required', Rule::in([
                MerchantBalanceTransactionType::MANUAL_CREDIT->value,
                MerchantBalanceTransactionType::MANUAL_DEBIT->value,
            ])],
            'adjustmentAmount' => ['required', 'numeric', 'gt:0'],
            'adjustmentCurrency' => ['required', 'string', 'max:16'],
            'adjustmentDescription' => ['required', 'string', 'max:1000'],
        ]);

        $amount = round((float) $validated['adjustmentAmount'], 2);
        $currency = strtoupper(trim($validated['adjustmentCurrency']));
        $type = MerchantBalanceTransactionType::from($validated['adjustmentType']);

        DB::transaction(function () use ($validated, $amount, $currency, $type) {
            $wallet = MerchantWallet::query()
                ->where('merchant_id', (int) $validated['adjustmentMerchantId'])
                ->where('currency', $currency)
                ->lockForUpdate()
                ->first();

            if (! $wallet) {
                if ($type->isDebit()) {
                    $this->addError('adjustmentAmount', 'Cannot debit a wallet that does not exist.');
                    return;
                }

                $wallet = MerchantWallet::create([
                    'merchant_id' => (int) $validated['adjustmentMerchantId'],
                    'currency' => $currency,
                    'balance' => 0,
                ]);

                $wallet = MerchantWallet::query()
                    ->whereKey($wallet->id)
                    ->lockForUpdate()
                    ->firstOrFail();
            }

            $before = round((float) $wallet->balance, 2);
            $after = $type->isCredit()
                ? round($before + $amount, 2)
                : round($before - $amount, 2);

            if ($after < 0) {
                $this->addError('adjustmentAmount', 'Insufficient merchant balance for this debit.');
                return;
            }

            $wallet->update(['balance' => $after]);

            MerchantBalanceTransaction::create([
                'merchant_id' => (int) $validated['adjustmentMerchantId'],
                'type' => $type,
                'amount' => $amount,
                'balance_before' => $before,
                'balance_after' => $after,
                'currency' => $currency,
                'reference_type' => null,
                'reference_id' => null,
                'description' => trim($validated['adjustmentDescription']),
                'created_by_user_id' => auth()->id(),
            ]);
        });

        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        session()->flash('status', 'Balance adjustment completed successfully.');
        $this->resetAdjustmentForm();
        $this->resetPage();
    }

    private function resetAdjustmentForm(): void
    {
        $this->showAdjustmentForm = false;
        $this->adjustmentMerchantId = '';
        $this->adjustmentType = MerchantBalanceTransactionType::MANUAL_CREDIT->value;
        $this->adjustmentAmount = '';
        $this->adjustmentCurrency = 'USD';
        $this->adjustmentDescription = '';
        $this->resetValidation();
    }

    public function with(): array
    {
        $transactions = MerchantBalanceTransaction::query()
            ->with(['merchant', 'createdBy'])
            ->when(trim($this->search) !== '', function ($query) {
                $search = trim($this->search);
                $query->where(function ($query) use ($search) {
                    $query
                        ->where('description', 'like', '%' . $search . '%')
                        ->orWhere('reference_type', 'like', '%' . $search . '%')
                        ->orWhere('reference_id', $search)
                        ->orWhereHas('merchant', fn ($q) => $q->where('name', 'like', '%' . $search . '%'));
                });
            })
            ->when($this->merchantFilter !== '', fn ($q) => $q->where('merchant_id', $this->merchantFilter))
            ->when($this->typeFilter !== '', fn ($q) => $q->where('type', $this->typeFilter))
            ->when($this->currencyFilter !== '', fn ($q) => $q->where('currency', $this->currencyFilter))
            ->when($this->dateFrom !== '', fn ($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn ($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->latest('id')
            ->paginate(30);

        return [
            'transactions' => $transactions,
            'merchants' => Merchant::query()->orderBy('name')->get(['id', 'name']),
            'types' => collect(MerchantBalanceTransactionType::cases())
                ->mapWithKeys(fn ($type) => [$type->value => $type->label()])
                ->toArray(),
            'currencies' => MerchantWallet::query()
                ->select('currency')->distinct()->orderBy('currency')->pluck('currency'),
        ];
    }
};
?>

<div class="space-y-6">

    <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
        <div>
            <div class="text-xs font-black uppercase tracking-[0.18em] text-lime-500">Finance</div>
            <h1 class="mt-2 text-2xl font-black wr-text">Transactions</h1>
            <p class="mt-2 text-sm wr-muted">
                Merchant balance ledger, automatic charges, deposits, refunds and manual adjustments.
            </p>
        </div>

        <button type="button" wire:click="openAdjustmentForm"
                class="rounded-xl bg-lime-400 px-5 py-3 text-sm font-black text-[#06111f] transition hover:bg-lime-300">
            + Balance Adjustment
        </button>
    </div>

    @if(session('status'))
        <div class="rounded-2xl border border-lime-500/20 bg-lime-500/10 px-5 py-4 text-sm font-bold text-lime-500">
            {{ session('status') }}
        </div>
    @endif

    @if($showAdjustmentForm)
        <div class="wr-panel rounded-2xl border p-6 sm:p-7">
            <div class="flex items-start justify-between gap-5">
                <div>
                    <div class="text-xs font-black uppercase tracking-[0.18em] text-lime-500">Manual Ledger Entry</div>
                    <h2 class="mt-2 text-xl font-black wr-text">Balance Adjustment</h2>
                    <p class="mt-1 text-sm wr-muted">
                        Credit or debit a merchant wallet. The operation is permanently recorded in the ledger.
                    </p>
                </div>
                <button type="button" wire:click="cancelAdjustmentForm"
                        class="text-sm font-bold wr-muted transition hover:text-lime-500">Close</button>
            </div>

            <form wire:submit="saveAdjustment" class="mt-7">
                <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-5">
                    <div>
                        <label class="mb-2 block text-sm font-bold wr-text-soft">Merchant</label>
                        <select wire:model="adjustmentMerchantId"
                                class="w-full rounded-xl border px-4 py-3.5 outline-none wr-text"
                                style="background:var(--wr-input);border-color:var(--wr-border);">
                            <option value="">Select merchant...</option>
                            @foreach($merchants as $merchant)
                                <option value="{{ $merchant->id }}">{{ $merchant->name }} (#{{ $merchant->id }})</option>
                            @endforeach
                        </select>
                        @error('adjustmentMerchantId') <div class="mt-2 text-xs font-bold text-red-400">{{ $message }}</div> @enderror
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-bold wr-text-soft">Operation</label>
                        <select wire:model="adjustmentType"
                                class="w-full rounded-xl border px-4 py-3.5 outline-none wr-text"
                                style="background:var(--wr-input);border-color:var(--wr-border);">
                            <option value="manual_credit">Manual Credit</option>
                            <option value="manual_debit">Manual Debit</option>
                        </select>
                        @error('adjustmentType') <div class="mt-2 text-xs font-bold text-red-400">{{ $message }}</div> @enderror
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-bold wr-text-soft">Amount</label>
                        <input type="number" min="0.01" step="0.01" wire:model="adjustmentAmount"
                               class="w-full rounded-xl border px-4 py-3.5 outline-none wr-text"
                               style="background:var(--wr-input);border-color:var(--wr-border);">
                        @error('adjustmentAmount') <div class="mt-2 text-xs font-bold text-red-400">{{ $message }}</div> @enderror
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-bold wr-text-soft">Currency</label>
                        <input type="text" maxlength="16" wire:model="adjustmentCurrency" placeholder="USD"
                               class="w-full rounded-xl border px-4 py-3.5 uppercase outline-none wr-text"
                               style="background:var(--wr-input);border-color:var(--wr-border);">
                        @error('adjustmentCurrency') <div class="mt-2 text-xs font-bold text-red-400">{{ $message }}</div> @enderror
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-bold wr-text-soft">Description</label>
                        <input type="text" wire:model="adjustmentDescription" placeholder="Reason for adjustment..."
                               class="w-full rounded-xl border px-4 py-3.5 outline-none wr-text"
                               style="background:var(--wr-input);border-color:var(--wr-border);">
                        @error('adjustmentDescription') <div class="mt-2 text-xs font-bold text-red-400">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="mt-6 flex gap-3">
                    <button type="submit" wire:loading.attr="disabled" wire:target="saveAdjustment"
                            wire:confirm="Confirm this merchant balance adjustment?"
                            class="rounded-xl bg-lime-400 px-6 py-3.5 font-black text-[#06111f] transition hover:bg-lime-300 disabled:opacity-50">
                        <span wire:loading.remove wire:target="saveAdjustment">Post Adjustment</span>
                        <span wire:loading wire:target="saveAdjustment">Posting...</span>
                    </button>
                    <button type="button" wire:click="cancelAdjustmentForm"
                            class="rounded-xl border px-6 py-3.5 text-sm font-bold wr-text"
                            style="border-color:var(--wr-border);">Cancel</button>
                </div>
            </form>
        </div>
    @endif

    <div class="wr-panel rounded-2xl border p-5">
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-6">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search..."
                   class="rounded-xl border px-4 py-3 text-sm outline-none wr-text"
                   style="background:var(--wr-input);border-color:var(--wr-border);">

            <select wire:model.live="merchantFilter" class="rounded-xl border px-4 py-3 text-sm wr-text"
                    style="background:var(--wr-input);border-color:var(--wr-border);">
                <option value="">All merchants</option>
                @foreach($merchants as $merchant)
                    <option value="{{ $merchant->id }}">{{ $merchant->name }}</option>
                @endforeach
            </select>

            <select wire:model.live="typeFilter" class="rounded-xl border px-4 py-3 text-sm wr-text"
                    style="background:var(--wr-input);border-color:var(--wr-border);">
                <option value="">All types</option>
                @foreach($types as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>

            <select wire:model.live="currencyFilter" class="rounded-xl border px-4 py-3 text-sm wr-text"
                    style="background:var(--wr-input);border-color:var(--wr-border);">
                <option value="">All currencies</option>
                @foreach($currencies as $currency)
                    <option value="{{ $currency }}">{{ $currency }}</option>
                @endforeach
            </select>

            <input type="date" wire:model.live="dateFrom" class="rounded-xl border px-4 py-3 text-sm wr-text"
                   style="background:var(--wr-input);border-color:var(--wr-border);">
            <input type="date" wire:model.live="dateTo" class="rounded-xl border px-4 py-3 text-sm wr-text"
                   style="background:var(--wr-input);border-color:var(--wr-border);">
        </div>

        @if($search !== '' || $merchantFilter !== '' || $typeFilter !== '' || $currencyFilter !== '' || $dateFrom !== '' || $dateTo !== '')
            <button type="button" wire:click="clearFilters" class="mt-4 text-xs font-black text-lime-500">
                Clear filters
            </button>
        @endif
    </div>

    <div class="wr-panel overflow-hidden rounded-xl border">
        <div class="overflow-x-auto">
            <table class="min-w-[1250px] w-full">
                <thead style="background:var(--wr-panel-secondary);">
                <tr class="border-b" style="border-color:var(--wr-border);">
                    <th class="px-5 py-4 text-left text-xs font-bold uppercase wr-muted">Date</th>
                    <th class="px-5 py-4 text-left text-xs font-bold uppercase wr-muted">Merchant</th>
                    <th class="px-5 py-4 text-left text-xs font-bold uppercase wr-muted">Type</th>
                    <th class="px-5 py-4 text-right text-xs font-bold uppercase wr-muted">Amount</th>
                    <th class="px-5 py-4 text-right text-xs font-bold uppercase wr-muted">Before</th>
                    <th class="px-5 py-4 text-right text-xs font-bold uppercase wr-muted">After</th>
                    <th class="px-5 py-4 text-left text-xs font-bold uppercase wr-muted">Reference</th>
                    <th class="px-5 py-4 text-left text-xs font-bold uppercase wr-muted">Description</th>
                    <th class="px-5 py-4 text-left text-xs font-bold uppercase wr-muted">Created By</th>
                </tr>
                </thead>

                <tbody>
                @forelse($transactions as $transaction)
                    <tr wire:key="balance-transaction-{{ $transaction->id }}"
                        class="border-b" style="border-color:var(--wr-border-soft);">
                        <td class="px-5 py-4 whitespace-nowrap">
                            <div class="text-sm font-semibold wr-text">{{ $transaction->created_at->format('Y-m-d') }}</div>
                            <div class="mt-1 text-xs wr-muted">{{ $transaction->created_at->format('H:i:s') }}</div>
                        </td>

                        <td class="px-5 py-4">
                            <div class="font-bold wr-text">{{ $transaction->merchant?->name ?: '—' }}</div>
                            <div class="mt-1 text-xs wr-muted">#{{ $transaction->merchant_id }}</div>
                        </td>

                        <td class="px-5 py-4">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-black {{ $transaction->type->isCredit() ? 'bg-lime-500/10 text-lime-500' : 'bg-red-500/10 text-red-400' }}">
                                    {{ $transaction->type->label() }}
                                </span>
                        </td>

                        <td class="px-5 py-4 text-right whitespace-nowrap">
                                <span class="font-black {{ $transaction->type->isCredit() ? 'text-lime-500' : 'text-red-400' }}">
                                    {{ $transaction->type->isCredit() ? '+' : '-' }}{{ number_format((float)$transaction->amount, 2) }}
                                </span>
                            <span class="ml-1 text-xs wr-muted">{{ $transaction->currency }}</span>
                        </td>

                        <td class="px-5 py-4 text-right text-sm wr-text">
                            {{ number_format((float)$transaction->balance_before, 2) }}
                        </td>

                        <td class="px-5 py-4 text-right text-sm font-bold wr-text">
                            {{ number_format((float)$transaction->balance_after, 2) }}
                        </td>

                        <td class="px-5 py-4">
                            @if($transaction->reference_type || $transaction->reference_id)
                                <div class="max-w-[180px] truncate text-xs wr-text-soft" title="{{ $transaction->reference_type }}">
                                    {{ class_basename($transaction->reference_type ?: 'Reference') }}
                                </div>
                                <div class="mt-1 text-xs wr-muted">#{{ $transaction->reference_id ?: '—' }}</div>
                            @else
                                <span class="text-xs wr-muted">Manual</span>
                            @endif
                        </td>

                        <td class="px-5 py-4">
                            <div class="max-w-[260px] text-sm wr-text-soft">
                                {{ $transaction->description ?: '—' }}
                            </div>
                        </td>

                        <td class="px-5 py-4">
                            @if($transaction->createdBy)
                                <div class="text-sm font-semibold wr-text">{{ $transaction->createdBy->name }}</div>
                                <div class="mt-1 text-xs wr-muted">{{ $transaction->createdBy->email }}</div>
                            @else
                                <span class="text-xs wr-muted">System</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-6 py-16 text-center">
                            <div class="font-semibold wr-text">No transactions found</div>
                            <div class="mt-2 text-sm wr-muted">The merchant balance ledger is currently empty.</div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if($transactions->hasPages())
            <div class="border-t px-5 py-4" style="border-color:var(--wr-border);">
                {{ $transactions->links() }}
            </div>
        @endif
    </div>
</div>
