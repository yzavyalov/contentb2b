<?php

use App\Enums\AccountApplicationStatus;
use App\Enums\AccountApplicationType;
use App\Enums\UserRole;
use App\Models\AccountApplication;
use Illuminate\Validation\Rule;
use Livewire\Component;

new class extends Component
{
    public ?string $selectedType = null;

    public string $displayName = '';
    public string $preferredLocale = 'en';

    public string $companyName = '';

    public string $contactEmail = '';
    public string $telegram = '';
    public string $phone = '';
    public string $website = '';
    public string $message = '';

    public function mount(): void
    {
        $user = auth()->user();

        abort_unless(
            $user && $user->role === UserRole::USER,
            403
        );

        $this->contactEmail = (string) $user->email;

        /*
         * Если заявка была отклонена, заранее заполняем
         * форму сохранёнными данными.
         */
        $application = $user->accountApplication;

        if (
            $application
            && $application->status === AccountApplicationStatus::REJECTED
        ) {
            $this->selectedType = $application->type->value;

            $this->displayName = (string) $application->display_name;
            $this->preferredLocale =
                (string) ($application->preferred_locale ?: 'en');

            $this->companyName =
                (string) $application->company_name;

            $this->contactEmail =
                (string) ($application->contact_email ?: $user->email);

            $this->telegram = (string) $application->telegram;
            $this->phone = (string) $application->phone;
            $this->website = (string) $application->website;
            $this->message = (string) $application->message;
        }
    }

    public function chooseContentCreator(): void
    {
        $this->selectedType =
            AccountApplicationType::CONTENT_MANAGER->value;

        $this->resetValidation();
    }

    public function chooseMerchant(): void
    {
        $this->selectedType =
            AccountApplicationType::MERCHANT->value;

        $this->resetValidation();
    }

    public function back(): void
    {
        $application = auth()->user()
            ?->accountApplication()
            ->first();

        if (
            $application
            && $application->status === AccountApplicationStatus::NEW
        ) {
            return;
        }

        $this->selectedType = null;
        $this->resetValidation();
    }

    public function submit(): void
    {
        $user = auth()->user();

        abort_unless(
            $user && $user->role === UserRole::USER,
            403
        );

        $existing = $user->accountApplication()->first();

        /*
         * Pending заявку повторно отправлять нельзя.
         */
        if (
            $existing
            && $existing->status === AccountApplicationStatus::NEW
        ) {
            return;
        }

        $this->validate([
            'selectedType' => [
                'required',
                Rule::in([
                    AccountApplicationType::CONTENT_MANAGER->value,
                    AccountApplicationType::MERCHANT->value,
                ]),
            ],

            'displayName' => [
                Rule::requiredIf(
                    $this->selectedType ===
                    AccountApplicationType::CONTENT_MANAGER->value
                ),
                'nullable',
                'string',
                'max:100',
            ],

            'preferredLocale' => [
                Rule::requiredIf(
                    $this->selectedType ===
                    AccountApplicationType::CONTENT_MANAGER->value
                ),
                'nullable',
                'string',
                'max:10',
            ],

            'companyName' => [
                Rule::requiredIf(
                    $this->selectedType ===
                    AccountApplicationType::MERCHANT->value
                ),
                'nullable',
                'string',
                'max:255',
            ],

            'contactEmail' => [
                'required',
                'email',
                'max:255',
            ],

            'telegram' => [
                'nullable',
                'string',
                'max:255',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:100',
            ],

            'website' => [
                'nullable',
                'url',
                'max:2048',
            ],

            'message' => [
                'nullable',
                'string',
                'max:3000',
            ],
        ]);

        /*
         * Требуем хотя бы один дополнительный способ связи.
         * Email уже есть всегда.
         */
        $application = AccountApplication::updateOrCreate(
            [
                'user_id' => $user->id,
            ],
            [
                'type' => $this->selectedType,
                'status' => AccountApplicationStatus::NEW->value,

                'display_name' =>
                    $this->selectedType ===
                    AccountApplicationType::CONTENT_MANAGER->value
                        ? trim($this->displayName)
                        : null,

                'preferred_locale' =>
                    $this->selectedType ===
                    AccountApplicationType::CONTENT_MANAGER->value
                        ? $this->preferredLocale
                        : null,

                'company_name' =>
                    $this->selectedType ===
                    AccountApplicationType::MERCHANT->value
                        ? trim($this->companyName)
                        : null,

                'contact_email' => trim($this->contactEmail),
                'telegram' => trim($this->telegram) ?: null,
                'phone' => trim($this->phone) ?: null,
                'website' => trim($this->website) ?: null,
                'message' => trim($this->message) ?: null,

                /*
                 * Повторная отправка rejected заявки.
                 */
                'reviewed_by_user_id' => null,
                'reviewed_at' => null,
                'rejection_reason' => null,
            ]
        );

        $this->selectedType = $application->type->value;

        session()->flash(
            'status',
            'Your application has been submitted for review.'
        );
    }

    public function with(): array
    {
        return [
            'application' => auth()
                ->user()
                ?->accountApplication()
                ->first(),
        ];
    }
};

?>

<div class="space-y-6">

    @if (session('status'))
        <div class="rounded-2xl border border-lime-300 bg-lime-50 px-5 py-4 text-sm font-bold text-lime-700">
            {{ session('status') }}
        </div>
    @endif

    {{-- ============================================================
         EXISTING APPLICATION
    ============================================================ --}}

    @if ($application && $application->status === \App\Enums\AccountApplicationStatus::NEW)

        <div class="mx-auto max-w-3xl py-10">

            <div class="rounded-3xl border border-[var(--wr-border)] bg-[var(--wr-panel)] p-8 shadow-sm sm:p-10">

                <div class="inline-flex rounded-full bg-amber-100 px-4 py-2 text-xs font-black uppercase tracking-wider text-amber-700">
                    Pending review
                </div>

                <h1 class="mt-6 text-3xl font-black text-[var(--wr-text)]">
                    Your application is being reviewed
                </h1>

                <p class="mt-3 max-w-2xl text-[var(--wr-muted)]">
                    Our team will review your application before access
                    to the selected workspace is activated.
                </p>

                <div class="mt-8 rounded-2xl border border-[var(--wr-border)] p-5">

                    <div class="text-xs font-black uppercase tracking-wider text-[var(--wr-muted)]">
                        Application type
                    </div>

                    <div class="mt-2 text-lg font-black text-[var(--wr-text)]">
                        {{ $application->type->label() }}
                    </div>

                    <div class="mt-5 text-xs font-black uppercase tracking-wider text-[var(--wr-muted)]">
                        Submitted
                    </div>

                    <div class="mt-2 font-semibold text-[var(--wr-text)]">
                        {{ $application->updated_at->format('M d, Y H:i') }}
                    </div>

                </div>

            </div>

        </div>

    @elseif ($application && $application->status === \App\Enums\AccountApplicationStatus::REJECTED)

        <div class="mx-auto max-w-4xl">

            <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 p-5">

                <div class="font-black text-red-700">
                    Application requires changes
                </div>

                @if ($application->rejection_reason)
                    <div class="mt-2 text-sm text-red-700">
                        {{ $application->rejection_reason }}
                    </div>
                @endif

                <div class="mt-2 text-sm text-red-600">
                    You can update the information below and submit
                    the application again.
                </div>

            </div>

            @include('components.user.partials.application-form')

        </div>

    @elseif ($selectedType)

        <div class="mx-auto max-w-4xl">
            @include('components.user.partials.application-form')
        </div>

    @else

        {{-- ========================================================
             INITIAL CHOICE
        ======================================================== --}}

        <div class="mx-auto max-w-6xl py-6">

            <div class="mb-10 max-w-3xl">

                <div class="text-xs font-black uppercase tracking-[0.18em] text-lime-500">
                    Get started
                </div>

                <h1 class="mt-3 text-3xl font-black tracking-tight text-[var(--wr-text)] sm:text-4xl">
                    How would you like to use wrangle.win?
                </h1>

                <p class="mt-4 text-base leading-7 text-[var(--wr-muted)]">
                    Choose your workspace. Your application will be
                    reviewed before access is activated.
                </p>

            </div>

            <div class="grid gap-6 lg:grid-cols-2">

                <button
                    type="button"
                    wire:click="chooseContentCreator"
                    class="group rounded-3xl border border-[var(--wr-border)] bg-[var(--wr-panel)] p-8 text-left shadow-sm transition hover:-translate-y-1 hover:border-lime-400 hover:shadow-xl"
                >

                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-lime-400 text-2xl font-black text-[#07111f]">
                        C
                    </div>

                    <h2 class="mt-7 text-2xl font-black text-[var(--wr-text)]">
                        Become a Content Creator
                    </h2>

                    <p class="mt-3 leading-7 text-[var(--wr-muted)]">
                        Create and manage professional prediction markets
                        for the wrangle.win content network.
                    </p>

                    <div class="mt-8 font-black text-lime-500">
                        Apply as Content Creator →
                    </div>

                </button>

                <button
                    type="button"
                    wire:click="chooseMerchant"
                    class="group rounded-3xl border border-[var(--wr-border)] bg-[var(--wr-panel)] p-8 text-left shadow-sm transition hover:-translate-y-1 hover:border-lime-400 hover:shadow-xl"
                >

                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-lime-400 text-2xl font-black text-[#07111f]">
                        M
                    </div>

                    <h2 class="mt-7 text-2xl font-black text-[var(--wr-text)]">
                        Create a Merchant
                    </h2>

                    <p class="mt-3 leading-7 text-[var(--wr-muted)]">
                        Receive prediction markets through wrangle.win
                        and integrate them into your own platform.
                    </p>

                    <div class="mt-8 font-black text-lime-500">
                        Apply as Merchant →
                    </div>

                </button>

            </div>

        </div>

    @endif

</div>
