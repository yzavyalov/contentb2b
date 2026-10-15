<?php

use App\Enums\MerchantBalanceTransactionType;
use App\Enums\UserRole;
use App\Models\Merchant;
use App\Models\MerchantBalanceTransaction;
use App\Models\MerchantBet;
use App\Models\MerchantWallet;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Component;

new class extends Component
{
    public string $month = '';

    public function mount(): void
    {
        $this->month = now()->format('Y-m');
    }

    public function updatedMonth(): void
    {
        if (! preg_match('/^\d{4}-\d{2}$/', $this->month)) {
            $this->month = now()->format('Y-m');

            return;
        }

        try {
            $date = Carbon::createFromFormat('!Y-m', $this->month);

            if (
                ! $date ||
                $date->format('Y-m') !== $this->month ||
                $date->startOfMonth()->gt(now()->startOfMonth())
            ) {
                $this->month = now()->format('Y-m');
            }
        } catch (\Throwable) {
            $this->month = now()->format('Y-m');
        }
    }

    public function previousMonth(): void
    {
        $this->month = Carbon::createFromFormat('!Y-m', $this->month)
            ->subMonth()
            ->format('Y-m');
    }

    public function nextMonth(): void
    {
        $nextMonth = Carbon::createFromFormat('!Y-m', $this->month)
            ->addMonth()
            ->startOfMonth();

        if ($nextMonth->lte(now()->startOfMonth())) {
            $this->month = $nextMonth->format('Y-m');
        }
    }

    public function with(): array
    {
        $selectedMonth = Carbon::createFromFormat('!Y-m', $this->month)
            ->startOfMonth();

        $monthStart = $selectedMonth->copy()->startOfMonth();
        $monthEnd = $selectedMonth->copy()->endOfMonth();

        $isCurrentMonth = $selectedMonth->isSameMonth(now());

        /*
         * Total merchants existing by the end of the selected month.
         */
        $merchants = Merchant::query()
            ->where('created_at', '<=', $monthEnd)
            ->count();

        /*
         * Content team existing by the end of the selected month.
         */
        $contentTeam = User::query()
            ->whereIn('role', [
                UserRole::CONTENT_MANAGER->value,
            ])
            ->where('created_at', '<=', $monthEnd)
            ->count();

        /*
         * Markets actually delivered during the selected month.
         */
        $marketsDelivered = MerchantBet::query()
            ->whereNotNull('delivered_at')
            ->whereBetween('delivered_at', [
                $monthStart,
                $monthEnd,
            ])
            ->count();

        /*
         * Real merchant balance top-ups during the selected month.
         * Refunds are intentionally excluded.
         */
        $balanceTopUps = MerchantBalanceTransaction::query()
            ->whereIn('type', [
                MerchantBalanceTransactionType::CRYPTO_DEPOSIT->value,
                MerchantBalanceTransactionType::BANK_DEPOSIT->value,
                MerchantBalanceTransactionType::MANUAL_CREDIT->value,
            ])
            ->whereBetween('created_at', [
                $monthStart,
                $monthEnd,
            ])
            ->sum('amount');

        /*
         * Current month: use actual wallet balances.
         *
         * Historical month: use the last balance_after transaction
         * for each merchant up to the end of that month.
         */
        if ($isCurrentMonth) {
            $merchantBalance = MerchantWallet::query()
                ->sum('balance');
        } else {
            $merchantBalance = MerchantBalanceTransaction::query()
                ->where('created_at', '<=', $monthEnd)
                ->whereIn('id', function ($query) use ($monthEnd) {
                    $query
                        ->from('merchant_balance_transactions as latest')
                        ->selectRaw('MAX(latest.id)')
                        ->where('latest.created_at', '<=', $monthEnd)
                        ->groupBy('latest.merchant_id');
                })
                ->sum('balance_after');
        }

        return [
            'selectedMonth' => $selectedMonth,
            'merchants' => $merchants,
            'contentTeam' => $contentTeam,
            'marketsDelivered' => $marketsDelivered,
            'balanceTopUps' => (float) $balanceTopUps,
            'merchantBalance' => (float) $merchantBalance,

            'canGoNext' => $selectedMonth
                ->copy()
                ->addMonth()
                ->startOfMonth()
                ->lte(now()->startOfMonth()),
        ];
    }
};
?>

<div>

    <!-- HEADER -->

    <div class="mb-8 flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">

        <div>
            <div class="text-xs font-black uppercase tracking-[0.18em] text-lime-500">
                Finance
            </div>

            <h1 class="mt-2 text-3xl font-black tracking-tight wr-text">
                Financial Dashboard
            </h1>

            <p class="mt-2 text-sm leading-6 wr-muted">
                Merchant and financial statistics by month.
            </p>
        </div>

        <!-- MONTH -->

        <div class="w-full sm:w-auto">
            <label class="mb-2 block text-xs font-black uppercase tracking-[0.14em] wr-muted">
                Month
            </label>

            <input
                type="month"
                wire:model.live="month"
                max="{{ now()->format('Y-m') }}"
                class="
                    rounded-xl
                    border border-[var(--wr-border)]
                    bg-[var(--wr-input)]
                    px-4 py-3
                    wr-text
                    outline-none
                    focus:border-lime-400/50
                "
            >
        </div>

    </div>


    <!-- MONTH NAVIGATION -->

    <div class="mb-6 flex items-center justify-between gap-4">

        <button
            type="button"
            wire:click="previousMonth"
            class="
                rounded-xl
                border border-[var(--wr-border)]
                px-4 py-2.5
                text-sm
                font-bold
                wr-text
                transition
                hover:border-lime-400/30
            "
        >
            ← Previous
        </button>

        <div class="text-center">
            <div class="text-xs font-black uppercase tracking-[0.14em] wr-muted">
                Selected period
            </div>

            <div class="mt-1 text-lg font-black wr-text">
                {{ $selectedMonth->format('F Y') }}
            </div>
        </div>

        <button
            type="button"
            wire:click="nextMonth"
            @disabled(!$canGoNext)
            class="
                rounded-xl
                border border-[var(--wr-border)]
                px-4 py-2.5
                text-sm
                font-bold
                wr-text
                transition
                hover:border-lime-400/30
                disabled:cursor-not-allowed
                disabled:opacity-30
            "
        >
            Next →
        </button>

    </div>


    <!-- STAT CARDS -->

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">

        <!-- MERCHANTS -->

        <div class="wr-panel rounded-2xl border border-[var(--wr-border)] p-5">

            <div class="text-xs font-black uppercase tracking-[0.14em] wr-muted">
                Merchants
            </div>

            <div class="mt-3 text-3xl font-black wr-text">
                {{ number_format($merchants) }}
            </div>

            <div class="mt-2 text-xs wr-muted">
                Total by end of month
            </div>

        </div>


        <!-- CONTENT MANAGERS -->

        <div class="wr-panel rounded-2xl border border-[var(--wr-border)] p-5">

            <div class="text-xs font-black uppercase tracking-[0.14em] wr-muted">
                Content Managers
            </div>

            <div class="mt-3 text-3xl font-black wr-text">
                {{ number_format($contentTeam) }}
            </div>

            <div class="mt-2 text-xs wr-muted">
                Total by end of month
            </div>

        </div>


        <!-- MARKETS DELIVERED -->

        <div class="wr-panel rounded-2xl border border-[var(--wr-border)] p-5">

            <div class="text-xs font-black uppercase tracking-[0.14em] wr-muted">
                Markets Delivered
            </div>

            <div class="mt-3 text-3xl font-black text-lime-500">
                {{ number_format($marketsDelivered) }}
            </div>

            <div class="mt-2 text-xs wr-muted">
                During selected month
            </div>

        </div>


        <!-- BALANCE TOP-UPS -->

        <div class="wr-panel rounded-2xl border border-[var(--wr-border)] p-5">

            <div class="text-xs font-black uppercase tracking-[0.14em] wr-muted">
                Balance Top-ups
            </div>

            <div class="mt-3 text-3xl font-black wr-text">
                ${{ number_format($balanceTopUps, 2) }}
            </div>

            <div class="mt-2 text-xs wr-muted">
                During selected month
            </div>

        </div>


        <!-- MERCHANT BALANCE -->

        <div class="wr-panel rounded-2xl border border-[var(--wr-border)] p-5">

            <div class="text-xs font-black uppercase tracking-[0.14em] wr-muted">
                Merchant Balance
            </div>

            <div class="mt-3 text-3xl font-black wr-text">
                ${{ number_format($merchantBalance, 2) }}
            </div>

            <div class="mt-2 text-xs wr-muted">
                Balance at end of period
            </div>

        </div>

    </div>

</div>
