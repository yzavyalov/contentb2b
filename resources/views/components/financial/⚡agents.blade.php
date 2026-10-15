<?php

use App\Enums\UserRole;
use App\Models\MerchantBet;
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

        /*
        |--------------------------------------------------------------------------
        | AGENTS
        |--------------------------------------------------------------------------
        |
        | Include every user with the AGENT role, even if the agent currently
        | has no merchants or no deliveries for the selected month.
        |
        */

        $agents = User::query()
            ->where('role', UserRole::AGENT->value)
            ->withCount('agentMerchants')
            ->orderBy('name')
            ->orderBy('email')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | MONTHLY DELIVERIES
        |--------------------------------------------------------------------------
        |
        | Count successfully delivered merchant_bets for merchants currently
        | assigned to each agent.
        |
        */

        $deliveryCounts = MerchantBet::query()
            ->join('merchants', 'merchants.id', '=', 'merchant_bets.merchant_id')
            ->whereNotNull('merchants.agent_id')
            ->whereNotNull('merchant_bets.delivered_at')
            ->whereBetween('merchant_bets.delivered_at', [
                $monthStart,
                $monthEnd,
            ])
            ->selectRaw('merchants.agent_id, COUNT(*) as delivered_count')
            ->groupBy('merchants.agent_id')
            ->pluck('delivered_count', 'merchants.agent_id');

        $agentStats = $agents->map(function (User $agent) use ($deliveryCounts) {
            return [
                'agent' => $agent,
                'merchants' => (int) $agent->agent_merchants_count,
                'delivered' => (int) ($deliveryCounts[$agent->id] ?? 0),
            ];
        });

        return [
            'agentStats' => $agentStats,

            'selectedMonth' => $selectedMonth,

            'totalAgents' => $agentStats->count(),

            'totalMerchants' => $agentStats->sum('merchants'),

            'totalDelivered' => $agentStats->sum('delivered'),

            'canGoNext' => $selectedMonth
                ->copy()
                ->addMonth()
                ->startOfMonth()
                ->lte(now()->startOfMonth()),
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
                Agents
            </h1>

            <p class="mt-2 text-sm wr-muted">
                Monthly performance report for agents and their assigned merchants.
            </p>
        </div>

        {{-- MONTH SELECTOR --}}
        <div>
            <label class="mb-2 block text-xs font-black uppercase tracking-[0.12em] wr-muted">
                Report month
            </label>

            <input
                type="month"
                wire:model.live="month"
                max="{{ now()->format('Y-m') }}"
                class="rounded-xl border px-4 py-3 text-sm font-semibold outline-none wr-text"
                style="background: var(--wr-input); border-color: var(--wr-border);"
            >
        </div>

    </div>


    {{-- SUMMARY --}}
    <div class="grid gap-4 sm:grid-cols-3">

        <div class="wr-panel rounded-xl border p-5">
            <div class="text-sm wr-muted">
                Agents
            </div>

            <div class="mt-2 text-2xl font-black wr-text">
                {{ number_format($totalAgents) }}
            </div>
        </div>

        <div class="wr-panel rounded-xl border p-5">
            <div class="text-sm wr-muted">
                Assigned merchants
            </div>

            <div class="mt-2 text-2xl font-black wr-text">
                {{ number_format($totalMerchants) }}
            </div>
        </div>

        <div class="wr-panel rounded-xl border p-5">
            <div class="text-sm wr-muted">
                Markets delivered
            </div>

            <div class="mt-2 text-2xl font-black wr-text">
                {{ number_format($totalDelivered) }}
            </div>

            <div class="mt-1 text-xs wr-muted">
                {{ $selectedMonth->format('F Y') }}
            </div>
        </div>

    </div>


    {{-- MONTH NAVIGATION --}}
    <div class="flex items-center justify-between gap-4">

        <button
            type="button"
            wire:click="previousMonth"
            class="rounded-xl border px-4 py-2.5 text-sm font-bold wr-text transition hover:bg-[var(--wr-hover)]"
            style="border-color: var(--wr-border);"
        >
            ← Previous month
        </button>

        <div class="text-center">
            <div class="font-black wr-text">
                {{ $selectedMonth->format('F Y') }}
            </div>

            <div class="mt-1 text-xs wr-muted">
                Agent performance
            </div>
        </div>

        <button
            type="button"
            wire:click="nextMonth"
            @disabled(!$canGoNext)
            class="rounded-xl border px-4 py-2.5 text-sm font-bold wr-text transition hover:bg-[var(--wr-hover)] disabled:cursor-not-allowed disabled:opacity-40"
            style="border-color: var(--wr-border);"
        >
            Next month →
        </button>

    </div>


    {{-- AGENTS TABLE --}}
    <div class="wr-panel overflow-hidden rounded-xl border">

        <div
            class="border-b px-5 py-4"
            style="border-color: var(--wr-border);"
        >
            <h2 class="font-black wr-text">
                Agent performance
            </h2>

            <p class="mt-1 text-xs wr-muted">
                {{ $selectedMonth->format('F Y') }}
            </p>
        </div>

        <div class="overflow-x-auto">

            <table class="w-full min-w-[800px]">

                <thead style="background: var(--wr-panel-secondary);">
                <tr class="border-b" style="border-color: var(--wr-border);">

                    <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wider wr-muted">
                        Agent
                    </th>

                    <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wider wr-muted">
                        Email
                    </th>

                    <th class="px-5 py-4 text-right text-xs font-bold uppercase tracking-wider wr-muted">
                        Merchants
                    </th>

                    <th class="px-5 py-4 text-right text-xs font-bold uppercase tracking-wider wr-muted">
                        Markets delivered
                    </th>

                </tr>
                </thead>

                <tbody>

                @forelse($agentStats as $stats)

                    <tr
                        wire:key="agent-report-{{ $stats['agent']->id }}"
                        class="border-b transition hover:bg-[var(--wr-hover)]"
                        style="border-color: var(--wr-border-soft);"
                    >

                        <td class="px-5 py-5">

                            <div class="font-black wr-text">
                                {{ $stats['agent']->name }}
                            </div>

                            <div class="mt-1 text-xs wr-muted">
                                Agent #{{ $stats['agent']->id }}
                            </div>

                        </td>

                        <td class="px-5 py-5">
                            <div class="text-sm font-semibold wr-text-soft">
                                {{ $stats['agent']->email }}
                            </div>
                        </td>

                        <td class="px-5 py-5 text-right">
                            <span class="text-lg font-black wr-text">
                                {{ number_format($stats['merchants']) }}
                            </span>
                        </td>

                        <td class="px-5 py-5 text-right">
                            <span class="text-lg font-black wr-text">
                                {{ number_format($stats['delivered']) }}
                            </span>
                        </td>

                    </tr>

                @empty

                    <tr>
                        <td colspan="4" class="px-6 py-16 text-center">

                            <div class="font-semibold wr-text">
                                No agents found
                            </div>

                            <div class="mt-2 text-sm wr-muted">
                                There are currently no users with the Agent role.
                            </div>

                        </td>
                    </tr>

                @endforelse

                </tbody>


                @if($agentStats->isNotEmpty())

                    <tfoot style="background: var(--wr-panel-secondary);">

                    <tr
                        class="border-t"
                        style="border-color: var(--wr-border);"
                    >

                        <td
                            colspan="2"
                            class="px-5 py-4 font-black wr-text"
                        >
                            Total
                        </td>

                        <td class="px-5 py-4 text-right text-lg font-black wr-text">
                            {{ number_format($totalMerchants) }}
                        </td>

                        <td class="px-5 py-4 text-right text-lg font-black wr-text">
                            {{ number_format($totalDelivered) }}
                        </td>

                    </tr>

                    </tfoot>

                @endif

            </table>

        </div>

    </div>

</div>
