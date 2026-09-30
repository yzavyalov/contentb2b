<?php

use App\Enums\AccountApplicationStatus;
use App\Enums\AccountApplicationType;
use App\Enums\UserRole;
use App\Models\AccountApplication;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

new class extends Component
{
    public string $filter = 'new';

    public ?int $selectedApplicationId = null;

    public string $rejectionReason = '';

    public function selectApplication(int $id): void
    {
        $application = AccountApplication::query()
            ->whereKey($id)
            ->firstOrFail();

        $this->selectedApplicationId = $application->id;
        $this->rejectionReason = '';

        $this->resetValidation();
    }

    public function closeApplication(): void
    {
        $this->selectedApplicationId = null;
        $this->rejectionReason = '';

        $this->resetValidation();
    }

    public function setFilter(string $filter): void
    {
        if (! in_array(
            $filter,
            ['new', 'approved', 'rejected', 'all'],
            true
        )) {
            return;
        }

        $this->filter = $filter;
        $this->selectedApplicationId = null;
        $this->rejectionReason = '';
    }

    public function approve(int $applicationId): void
    {
        DB::transaction(function () use ($applicationId) {
            $application = AccountApplication::query()->with('user')->whereKey($applicationId)->lockForUpdate()->firstOrFail();
            if ($application->status !== AccountApplicationStatus::NEW) return;
            $user = $application->user;
            if (! $user) throw new \RuntimeException('Application user was not found.');
            if ($user->role !== UserRole::USER) throw new \RuntimeException('This user already has another role.');
            if ($application->type === AccountApplicationType::MERCHANT) {
                $user->update(['role' => UserRole::MERCHANT]);
            } elseif ($application->type === AccountApplicationType::CONTENT_MANAGER) {
                $user->update(['role' => UserRole::CONTENT_MANAGER]);
            } else {
                throw new \RuntimeException('Unsupported account application type.');
            }
            $application->update([
                'status' => AccountApplicationStatus::APPROVED,
                'reviewed_by_user_id' => auth()->id(),
                'reviewed_at' => now(),
                'rejection_reason' => null,
            ]);
        }, 3);
        $this->selectedApplicationId = null;
        $this->rejectionReason = '';
        $this->resetValidation();
        session()->flash('status', 'Application approved successfully.');
    }

    public function reject(int $applicationId): void
    {
        $this->validate(['rejectionReason' => ['required', 'string', 'min:3', 'max:2000']]);
        DB::transaction(function () use ($applicationId) {
            $application = AccountApplication::query()->whereKey($applicationId)->lockForUpdate()->firstOrFail();
            if ($application->status !== AccountApplicationStatus::NEW) return;
            $application->update([
                'status' => AccountApplicationStatus::REJECTED,
                'reviewed_by_user_id' => auth()->id(),
                'reviewed_at' => now(),
                'rejection_reason' => trim($this->rejectionReason),
            ]);
        }, 3);
        $this->selectedApplicationId = null;
        $this->rejectionReason = '';
        $this->resetValidation();
        session()->flash('status', 'Application rejected.');
    }

    public function with(): array
    {
        $query = AccountApplication::query()
            ->with([
                'user',
                'reviewedBy',
            ])
            ->latest('id');

        if ($this->filter !== 'all') {
            $query->where('status', $this->filter);
        }

        $selectedApplication = null;

        if ($this->selectedApplicationId) {
            $selectedApplication = AccountApplication::query()
                ->with([
                    'user',
                    'reviewedBy',
                ])
                ->find($this->selectedApplicationId);
        }

        return [
            'applications' => $query->get(),

            'selectedApplication' =>
                $selectedApplication,

            'newCount' => AccountApplication::query()
                ->where(
                    'status',
                    AccountApplicationStatus::NEW->value
                )
                ->count(),

            'approvedCount' => AccountApplication::query()
                ->where(
                    'status',
                    AccountApplicationStatus::APPROVED->value
                )
                ->count(),

            'rejectedCount' => AccountApplication::query()
                ->where(
                    'status',
                    AccountApplicationStatus::REJECTED->value
                )
                ->count(),
        ];
    }
};

?>

<div class="space-y-6">

    @if (session('status'))
        <div class="rounded-2xl border border-lime-400/30 bg-lime-400/10 px-5 py-4 text-sm font-bold text-lime-400">
            {{ session('status') }}
        </div>
    @endif

    {{-- =========================================================
         HEADER
    ========================================================== --}}

    <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">

        <div>
            <div class="text-xs font-black uppercase tracking-[0.20em] text-lime-500">
                Administration
            </div>

            <h1 class="mt-2 text-3xl font-black text-[var(--wr-text)]">
                Account Applications
            </h1>

            <p class="mt-2 max-w-3xl text-sm leading-6 text-[var(--wr-muted)]">
                Review applications from users requesting access
                as Content Creators or Merchants.
            </p>
        </div>

        <div class="flex flex-wrap gap-3">

            <div class="min-w-[130px] rounded-2xl border border-[var(--wr-border)] bg-[var(--wr-panel)] px-5 py-3">
                <div class="text-xs font-bold uppercase text-[var(--wr-muted)]">
                    New
                </div>

                <div class="mt-1 text-2xl font-black text-amber-400">
                    {{ $newCount }}
                </div>
            </div>

            <div class="min-w-[130px] rounded-2xl border border-[var(--wr-border)] bg-[var(--wr-panel)] px-5 py-3">
                <div class="text-xs font-bold uppercase text-[var(--wr-muted)]">
                    Approved
                </div>

                <div class="mt-1 text-2xl font-black text-lime-400">
                    {{ $approvedCount }}
                </div>
            </div>

            <div class="min-w-[130px] rounded-2xl border border-[var(--wr-border)] bg-[var(--wr-panel)] px-5 py-3">
                <div class="text-xs font-bold uppercase text-[var(--wr-muted)]">
                    Rejected
                </div>

                <div class="mt-1 text-2xl font-black text-red-400">
                    {{ $rejectedCount }}
                </div>
            </div>

        </div>

    </div>


    {{-- =========================================================
         FILTERS
    ========================================================== --}}

    <div class="flex flex-wrap gap-2">

        @foreach ([
            'new' => 'New',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'all' => 'All',
        ] as $value => $label)

            <button
                type="button"
                wire:click="setFilter('{{ $value }}')"
                class="
                    rounded-xl border px-4 py-2 text-sm font-black transition
                    {{ $filter === $value
                        ? 'border-lime-400 bg-lime-400 text-[#07111f]'
                        : 'border-[var(--wr-border)] bg-[var(--wr-panel)] text-[var(--wr-text)] hover:border-lime-400/50'
                    }}
                "
            >
                {{ $label }}
            </button>

        @endforeach

    </div>


    {{-- =========================================================
         APPLICATION LIST
    ========================================================== --}}

    <div class="overflow-hidden rounded-3xl border border-[var(--wr-border)] bg-[var(--wr-panel)]">

        <div class="overflow-x-auto">

            <table class="min-w-full">

                <thead class="border-b border-[var(--wr-border)]">

                <tr class="text-left text-xs font-black uppercase tracking-[0.12em] text-[var(--wr-muted)]">

                    <th class="px-6 py-4">
                        Applicant
                    </th>

                    <th class="px-6 py-4">
                        Requested access
                    </th>

                    <th class="px-6 py-4">
                        Contact
                    </th>

                    <th class="px-6 py-4">
                        Status
                    </th>

                    <th class="px-6 py-4">
                        Submitted
                    </th>

                    <th class="px-6 py-4"></th>

                </tr>

                </thead>

                <tbody class="divide-y divide-[var(--wr-border)]">

                @forelse ($applications as $application)

                    <tr class="transition hover:bg-white/[0.02]">

                        {{-- USER --}}

                        <td class="px-6 py-5">

                            <div class="font-black text-[var(--wr-text)]">
                                {{ $application->user?->name ?? 'Unknown user' }}
                            </div>

                            <div class="mt-1 text-sm text-[var(--wr-muted)]">
                                {{ $application->user?->email ?? 'вЂ”' }}
                            </div>

                            <div class="mt-1 text-xs text-[var(--wr-muted)]">
                                User #{{ $application->user_id }}
                            </div>

                        </td>


                        {{-- TYPE --}}

                        <td class="px-6 py-5">

                            @if ($application->type->value === 'merchant')

                                <span class="inline-flex rounded-full bg-blue-400/10 px-3 py-1 text-xs font-black text-blue-400">
                                        MERCHANT
                                    </span>

                                <div class="mt-2 font-bold text-[var(--wr-text)]">
                                    {{ $application->company_name }}
                                </div>

                            @else

                                <span class="inline-flex rounded-full bg-violet-400/10 px-3 py-1 text-xs font-black text-violet-400">
                                        CONTENT CREATOR
                                    </span>

                                <div class="mt-2 font-bold text-[var(--wr-text)]">
                                    {{ $application->display_name }}
                                </div>

                            @endif

                        </td>


                        {{-- CONTACT --}}

                        <td class="px-6 py-5 text-sm">

                            <div class="text-[var(--wr-text)]">
                                {{ $application->contact_email ?: 'вЂ”' }}
                            </div>

                            @if ($application->telegram)
                                <div class="mt-1 text-[var(--wr-muted)]">
                                    {{ $application->telegram }}
                                </div>
                            @endif

                            @if ($application->phone)
                                <div class="mt-1 text-[var(--wr-muted)]">
                                    {{ $application->phone }}
                                </div>
                            @endif

                        </td>


                        {{-- STATUS --}}

                        <td class="px-6 py-5">

                            @if ($application->status === \App\Enums\AccountApplicationStatus::NEW)

                                <span class="inline-flex rounded-full bg-amber-400/10 px-3 py-1 text-xs font-black text-amber-400">
                                        NEW
                                    </span>

                            @elseif ($application->status === \App\Enums\AccountApplicationStatus::APPROVED)

                                <span class="inline-flex rounded-full bg-lime-400/10 px-3 py-1 text-xs font-black text-lime-400">
                                        APPROVED
                                    </span>

                            @else

                                <span class="inline-flex rounded-full bg-red-400/10 px-3 py-1 text-xs font-black text-red-400">
                                        REJECTED
                                    </span>

                            @endif

                        </td>


                        {{-- DATE --}}

                        <td class="whitespace-nowrap px-6 py-5 text-sm text-[var(--wr-muted)]">

                            {{ $application->created_at?->format('d.m.Y') }}

                            <div class="mt-1 text-xs">
                                {{ $application->created_at?->format('H:i') }}
                            </div>

                        </td>


                        {{-- ACTION --}}

                        <td class="px-6 py-5 text-right">

                            <button
                                type="button"
                                wire:click="selectApplication({{ $application->id }})"
                                class="rounded-xl border border-[var(--wr-border)] px-4 py-2 text-sm font-black text-[var(--wr-text)] transition hover:border-lime-400 hover:text-lime-400"
                            >
                                Review
                            </button>

                        </td>

                    </tr>

                @empty

                    <tr>
                        <td
                            colspan="6"
                            class="px-6 py-20 text-center"
                        >

                            <div class="text-lg font-black text-[var(--wr-text)]">
                                No applications
                            </div>

                            <div class="mt-2 text-sm text-[var(--wr-muted)]">
                                There are no applications in this section.
                            </div>

                        </td>
                    </tr>

                @endforelse

                </tbody>

            </table>

        </div>

    </div>


    {{-- =========================================================
         SELECTED APPLICATION
    ========================================================== --}}

    @if ($selectedApplication)

        <div class="rounded-3xl border border-lime-400/20 bg-[var(--wr-panel)] p-6 sm:p-8">

            <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">

                <div>

                    <div class="text-xs font-black uppercase tracking-[0.18em] text-lime-500">
                        Application #{{ $selectedApplication->id }}
                    </div>

                    <h2 class="mt-2 text-2xl font-black text-[var(--wr-text)]">

                        @if ($selectedApplication->type->value === 'merchant')
                            Merchant Application
                        @else
                            Content Creator Application
                        @endif

                    </h2>

                </div>

                <button
                    type="button"
                    wire:click="closeApplication"
                    class="rounded-xl border border-[var(--wr-border)] px-4 py-2 text-sm font-bold text-[var(--wr-muted)] transition hover:text-[var(--wr-text)]"
                >
                    Close
                </button>

            </div>


            {{-- USER INFO --}}

            <div class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">

                <div class="rounded-2xl border border-[var(--wr-border)] p-4">
                    <div class="text-xs font-bold uppercase text-[var(--wr-muted)]">
                        Account
                    </div>

                    <div class="mt-2 font-black text-[var(--wr-text)]">
                        {{ $selectedApplication->user?->name }}
                    </div>

                    <div class="mt-1 text-sm text-[var(--wr-muted)]">
                        {{ $selectedApplication->user?->email }}
                    </div>
                </div>


                @if ($selectedApplication->type->value === 'merchant')

                    <div class="rounded-2xl border border-[var(--wr-border)] p-4">

                        <div class="text-xs font-bold uppercase text-[var(--wr-muted)]">
                            Company
                        </div>

                        <div class="mt-2 font-black text-[var(--wr-text)]">
                            {{ $selectedApplication->company_name }}
                        </div>

                    </div>

                @else

                    <div class="rounded-2xl border border-[var(--wr-border)] p-4">

                        <div class="text-xs font-bold uppercase text-[var(--wr-muted)]">
                            Display name
                        </div>

                        <div class="mt-2 font-black text-[var(--wr-text)]">
                            {{ $selectedApplication->display_name }}
                        </div>

                    </div>


                    <div class="rounded-2xl border border-[var(--wr-border)] p-4">

                        <div class="text-xs font-bold uppercase text-[var(--wr-muted)]">
                            Preferred language
                        </div>

                        <div class="mt-2 font-black text-[var(--wr-text)]">
                            {{ strtoupper($selectedApplication->preferred_locale ?? '') }}
                        </div>

                    </div>

                @endif


                <div class="rounded-2xl border border-[var(--wr-border)] p-4">

                    <div class="text-xs font-bold uppercase text-[var(--wr-muted)]">
                        Contact email
                    </div>

                    <div class="mt-2 break-all font-black text-[var(--wr-text)]">
                        {{ $selectedApplication->contact_email ?: 'вЂ”' }}
                    </div>

                </div>


                <div class="rounded-2xl border border-[var(--wr-border)] p-4">

                    <div class="text-xs font-bold uppercase text-[var(--wr-muted)]">
                        Telegram
                    </div>

                    <div class="mt-2 font-black text-[var(--wr-text)]">
                        {{ $selectedApplication->telegram ?: 'вЂ”' }}
                    </div>

                </div>


                <div class="rounded-2xl border border-[var(--wr-border)] p-4">

                    <div class="text-xs font-bold uppercase text-[var(--wr-muted)]">
                        Phone
                    </div>

                    <div class="mt-2 font-black text-[var(--wr-text)]">
                        {{ $selectedApplication->phone ?: 'вЂ”' }}
                    </div>

                </div>


                <div class="rounded-2xl border border-[var(--wr-border)] p-4">

                    <div class="text-xs font-bold uppercase text-[var(--wr-muted)]">
                        Website
                    </div>

                    <div class="mt-2 break-all font-black text-[var(--wr-text)]">
                        {{ $selectedApplication->website ?: 'вЂ”' }}
                    </div>

                </div>

            </div>


            {{-- MESSAGE --}}

            @if ($selectedApplication->message)

                <div class="mt-6 rounded-2xl border border-[var(--wr-border)] p-5">

                    <div class="text-xs font-bold uppercase text-[var(--wr-muted)]">
                        Applicant message
                    </div>

                    <div class="mt-3 whitespace-pre-line text-sm leading-7 text-[var(--wr-text)]">
                        {{ $selectedApplication->message }}
                    </div>

                </div>

            @endif


            {{-- REVIEW STATUS --}}

            @if ($selectedApplication->status !== \App\Enums\AccountApplicationStatus::NEW)

                <div class="mt-6 rounded-2xl border border-[var(--wr-border)] p-5">

                    <div class="text-xs font-bold uppercase text-[var(--wr-muted)]">
                        Reviewed
                    </div>

                    <div class="mt-2 text-sm text-[var(--wr-text)]">
                        {{ $selectedApplication->reviewedBy?->name ?? 'вЂ”' }}

                        @if ($selectedApplication->reviewed_at)
                            В· {{ $selectedApplication->reviewed_at->format('d.m.Y H:i') }}
                        @endif
                    </div>

                    @if ($selectedApplication->rejection_reason)

                        <div class="mt-4 rounded-xl bg-red-400/10 p-4 text-sm text-red-400">
                            {{ $selectedApplication->rejection_reason }}
                        </div>

                    @endif

                </div>

            @endif


            {{-- DECISION PANEL --}}

            @if ($selectedApplication->status === \App\Enums\AccountApplicationStatus::NEW)

                <div class="mt-8 border-t border-[var(--wr-border)] pt-8">

                    <div class="text-lg font-black text-[var(--wr-text)]">
                        Decision
                    </div>

                    <p class="mt-1 text-sm text-[var(--wr-muted)]">
                        Approve or reject this application.
                    </p>

                    <div class="mt-5 grid gap-5 lg:grid-cols-[1fr_auto]">

                        <div>

                            <label class="mb-2 block text-sm font-bold text-[var(--wr-text)]">
                                Rejection reason
                            </label>

                            <textarea
                                wire:model="rejectionReason"
                                rows="3"
                                class="w-full rounded-xl border border-[var(--wr-border)] bg-[var(--wr-input)] px-4 py-3 text-[var(--wr-text)] outline-none transition focus:border-red-400"
                                placeholder="Required only when rejecting the application..."
                            ></textarea>

                            @error('rejectionReason')
                            <div class="mt-2 text-sm font-semibold text-red-400">{{ $message }}</div>
                            @enderror

                        </div>


                        <div class="flex items-end gap-3">

                            <button
                                type="button"
                                wire:click="reject({{ $selectedApplication->id }})"
                                wire:loading.attr="disabled"
                                wire:target="reject"
                                class="rounded-xl border border-red-400/40 px-5 py-3 font-black text-red-400 transition hover:bg-red-400/10 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <span wire:loading.remove wire:target="reject">Reject</span>
                                <span wire:loading wire:target="reject">Rejecting...</span>
                            </button>

                            <button
                                type="button"
                                wire:click="approve({{ $selectedApplication->id }})"
                                wire:loading.attr="disabled"
                                wire:target="approve"
                                class="rounded-xl bg-lime-400 px-6 py-3 font-black text-[#07111f] transition hover:bg-lime-300 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <span wire:loading.remove wire:target="approve">Approve</span>
                                <span wire:loading wire:target="approve">Approving...</span>
                            </button>

                        </div>

                    </div>

                </div>

            @endif

        </div>

    @endif

</div>
