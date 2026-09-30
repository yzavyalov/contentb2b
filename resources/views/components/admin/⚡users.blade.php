<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Component;

new class extends Component
{
    public string $search = '';

    public array $roles = [];

    /*
    |--------------------------------------------------------------------------
    | CREATE USER
    |--------------------------------------------------------------------------
    */

    public bool $showCreateUser = false;

    public string $newName = '';
    public string $newEmail = '';
    public string $newRole = 'user';

    public ?string $generatedPassword = null;
    public ?int $createdUserId = null;

    /*
    |--------------------------------------------------------------------------
    | RESET PASSWORD
    |--------------------------------------------------------------------------
    */

    public ?int $passwordUserId = null;
    public ?string $passwordUserEmail = null;
    public ?string $generatedNewPassword = null;


    public function mount(): void
    {
        $this->roles = collect(UserRole::cases())
            ->mapWithKeys(fn (UserRole $role) => [
                $role->value => $role->label(),
            ])
            ->toArray();
    }


    /*
    |--------------------------------------------------------------------------
    | CHANGE ROLE
    |--------------------------------------------------------------------------
    */

    public function changeRole(int $userId, string $newRole): void
    {
        $user = User::withCount('merchants')->findOrFail($userId);

        $allowedRoles = array_column(
            UserRole::cases(),
            'value'
        );

        if (! in_array($newRole, $allowedRoles, true)) {

            $this->addError(
                'role_' . $userId,
                'Invalid user role.'
            );

            return;
        }


        /*
         * Merchant с привязанными merchants
         * нельзя перевести на другую роль.
         */
        if (
            $user->role === UserRole::MERCHANT
            && $user->merchants_count > 0
            && $newRole !== UserRole::MERCHANT->value
        ) {

            $this->addError(
                'role_' . $userId,
                'This user has attached merchants. Remove or reassign them before changing the role.'
            );

            return;
        }


        $this->resetErrorBag(
            'role_' . $userId
        );

        $user->role = UserRole::from($newRole);

        $user->save();

        session()->flash(
            'status',
            'User role updated successfully.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | OPEN CREATE USER FORM
    |--------------------------------------------------------------------------
    */

    public function createUserForm(): void
    {
        $this->reset([
            'newName',
            'newEmail',
            'generatedPassword',
            'createdUserId',

            'passwordUserId',
            'passwordUserEmail',
            'generatedNewPassword',
        ]);

        $this->newRole = UserRole::USER->value;

        $this->resetValidation();

        $this->showCreateUser = true;
    }


    public function cancelCreateUser(): void
    {
        $this->showCreateUser = false;

        $this->reset([
            'newName',
            'newEmail',
            'generatedPassword',
            'createdUserId',
        ]);

        $this->newRole = UserRole::USER->value;

        $this->resetValidation();
    }


    /*
    |--------------------------------------------------------------------------
    | CREATE USER
    |--------------------------------------------------------------------------
    */

    public function createUser(): void
    {
        $this->validate([
            'newName' => [
                'required',
                'string',
                'max:255',
            ],

            'newEmail' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
            ],

            'newRole' => [
                'required',
                'string',
            ],
        ]);

        $allowedRoles = array_column(
            UserRole::cases(),
            'value'
        );

        if (! in_array($this->newRole, $allowedRoles, true)) {
            $this->addError(
                'newRole',
                'Invalid role.'
            );

            return;
        }

        $plainPassword = Str::password(16);

        $user = User::create([
            'name' => trim($this->newName),
            'email' => strtolower(trim($this->newEmail)),
            'password' => Hash::make($plainPassword),
            'role' => UserRole::from($this->newRole),
        ]);

        $this->createdUserId = $user->id;

        $this->generatedPassword = $plainPassword;

        session()->flash(
            'status',
            'User created successfully.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | RESET USER PASSWORD
    |--------------------------------------------------------------------------
    */

    public function generateNewPassword(int $userId): void
    {
        $user = User::findOrFail($userId);

        $plainPassword = Str::password(16);

        $user->password = Hash::make($plainPassword);

        $user->save();

        $this->passwordUserId = $user->id;
        $this->passwordUserEmail = $user->email;
        $this->generatedNewPassword = $plainPassword;
    }


    public function closeGeneratedPassword(): void
    {
        $this->reset([
            'passwordUserId',
            'passwordUserEmail',
            'generatedNewPassword',
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | DATA
    |--------------------------------------------------------------------------
    */

    public function with(): array
    {
        return [

            'users' => User::query()

                ->withCount('merchants')

                ->when(
                    trim($this->search) !== '',
                    function ($query) {

                        $search = trim(
                            $this->search
                        );


                        $query->where(
                            function ($query) use ($search) {

                                $query
                                    ->where(
                                        'name',
                                        'like',
                                        '%' . $search . '%'
                                    )
                                    ->orWhere(
                                        'email',
                                        'like',
                                        '%' . $search . '%'
                                    );

                            }
                        );

                    }
                )

                ->orderBy('id', 'desc')

                ->get(),

        ];
    }
};

?>


<div>

    <!-- ========================================================= -->
    <!-- HEADER -->
    <!-- ========================================================= -->

    <div class="mb-8 flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">

        <div>

            <div class="text-xs font-black uppercase tracking-[0.18em] text-lime-500">
                Administration
            </div>

            <h1 class="mt-2 text-3xl font-black tracking-tight wr-text">
                Users
            </h1>

            <p class="mt-2 text-sm leading-6 wr-muted">
                Search users, manage roles, passwords and merchant accounts.
            </p>

        </div>


        <div class="flex w-full flex-col gap-4 sm:flex-row xl:w-auto xl:items-end">

            <!-- SEARCH -->

            <div class="w-full sm:min-w-[360px]">

                <label
                    for="users-search"
                    class="mb-2 block text-xs font-black uppercase tracking-[0.14em] wr-muted"
                >
                    Search user
                </label>

                <div class="relative">

                    <input
                        id="users-search"
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Name or email..."
                        class="
                            w-full
                            rounded-xl
                            border border-[var(--wr-border)]
                            wr-panel
                            px-4 py-3.5
                            wr-text
                            outline-none
                            transition
                            placeholder:wr-muted
                            focus:border-lime-400/50
                        "
                    >

                    <div
                        wire:loading
                        wire:target="search"
                        class="absolute right-4 top-1/2 -translate-y-1/2 text-xs font-bold text-lime-500"
                    >
                        Searching...
                    </div>

                </div>

            </div>


            <!-- CREATE USER -->

            <button
                type="button"
                wire:click="createUserForm"
                class="
                    inline-flex
                    h-[52px]
                    items-center
                    justify-center
                    whitespace-nowrap
                    rounded-xl
                    bg-lime-400
                    px-6
                    text-sm
                    font-black
                    text-[#07111f]
                    transition
                    hover:bg-lime-300
                "
            >
                + Create User
            </button>

        </div>

    </div>


    <!-- ========================================================= -->
    <!-- FLASH -->
    <!-- ========================================================= -->

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


    <!-- ========================================================= -->
    <!-- CREATE USER -->
    <!-- ========================================================= -->

    @if($showCreateUser)

        <div
            class="
                mb-8
                rounded-3xl
                border border-[var(--wr-border)]
                wr-panel
                p-7
                sm:p-8
            "
        >

            <div class="flex items-start justify-between gap-5">

                <div>

                    <div class="text-xs font-black uppercase tracking-[0.18em] text-lime-500">
                        New Account
                    </div>

                    <h2 class="mt-2 text-2xl font-black wr-text">
                        Create User
                    </h2>

                </div>


                <button
                    type="button"
                    wire:click="cancelCreateUser"
                    class="
                        text-sm
                        font-bold
                        wr-muted
                        transition
                        hover:wr-text
                    "
                >
                    Close
                </button>

            </div>


            @if(!$createdUserId)

                <form
                    wire:submit="createUser"
                    class="mt-7"
                >

                    <div class="grid gap-5 lg:grid-cols-3">

                        <!-- NAME -->

                        <div>

                            <label
                                class="mb-2 block text-sm font-bold wr-text"
                            >
                                Name
                            </label>

                            <input
                                type="text"
                                wire:model="newName"
                                placeholder="John Smith"
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

                            @error('newName')

                            <div class="mt-2 text-xs font-bold text-red-400">
                                {{ $message }}
                            </div>

                            @enderror

                        </div>


                        <!-- EMAIL -->

                        <div>

                            <label
                                class="mb-2 block text-sm font-bold wr-text"
                            >
                                Email
                            </label>

                            <input
                                type="email"
                                wire:model="newEmail"
                                placeholder="client@example.com"
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

                            @error('newEmail')

                            <div class="mt-2 text-xs font-bold text-red-400">
                                {{ $message }}
                            </div>

                            @enderror

                        </div>


                        <!-- ROLE -->

                        <div>

                            <label
                                class="mb-2 block text-sm font-bold wr-text"
                            >
                                Role
                            </label>

                            <select
                                wire:model="newRole"
                                class="
                                    w-full
                                    rounded-xl
                                    border border-[var(--wr-border)]
                                    bg-[var(--wr-input)]
                                    px-4 py-3.5
                                    wr-text
                                    outline-none
                                    focus:border-lime-400/50
                                "
                            >

                                @foreach($roles as $roleValue => $roleLabel)

                                    <option value="{{ $roleValue }}">
                                        {{ $roleLabel }}
                                    </option>

                                @endforeach

                            </select>

                            @error('newRole')

                            <div class="mt-2 text-xs font-bold text-red-400">
                                {{ $message }}
                            </div>

                            @enderror

                        </div>

                    </div>


                    <div class="mt-6">

                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            wire:target="createUser"
                            class="
                                rounded-xl
                                bg-lime-400
                                px-6 py-3.5
                                font-black
                                text-[#07111f]
                                transition
                                hover:bg-lime-300
                                disabled:cursor-not-allowed
                                disabled:opacity-50
                            "
                        >
                            <span
                                wire:loading.remove
                                wire:target="createUser"
                            >
                                Generate User
                            </span>

                            <span
                                wire:loading
                                wire:target="createUser"
                            >
                                Creating...
                            </span>

                        </button>

                    </div>

                </form>


            @else

                <!-- CREATED USER CREDENTIALS -->

                <div class="mt-7">

                    <div
                        class="
                            rounded-2xl
                            border border-lime-400/30
                            bg-lime-400/[0.05]
                            p-6
                        "
                    >

                        <div class="text-xs font-black uppercase tracking-[0.14em] text-lime-500">
                            User Created
                        </div>


                        <div class="mt-6 grid gap-6 md:grid-cols-2">

                            <div>

                                <div class="text-xs font-bold uppercase tracking-[0.14em] wr-muted">
                                    Login
                                </div>

                                <div class="mt-2 select-all break-all font-mono text-lg font-bold wr-text">
                                    {{ $newEmail }}
                                </div>

                            </div>


                            <div>

                                <div class="text-xs font-bold uppercase tracking-[0.14em] wr-muted">
                                    Generated Password
                                </div>

                                <div
                                    class="
                                        mt-2
                                        select-all
                                        break-all
                                        font-mono
                                        text-lg
                                        font-black
                                        text-lime-500
                                    "
                                >
                                    {{ $generatedPassword }}
                                </div>

                            </div>

                        </div>


                        <div
                            class="
                                mt-6
                                rounded-xl
                                border border-amber-400/20
                                bg-amber-400/[0.05]
                                p-4
                                text-sm
                                leading-6
                                text-amber-300
                            "
                        >
                            Save these credentials now. The generated password will not be available again after this form is closed.
                        </div>


                        @if($newRole === \App\Enums\UserRole::MERCHANT->value)

                            <div class="mt-5">

                                <a
                                    href="{{ route('admin.merchants.user', $createdUserId) }}"
                                    class="
                                        inline-flex
                                        items-center
                                        rounded-xl
                                        border border-lime-400/30
                                        px-5 py-3
                                        text-sm
                                        font-black
                                        text-lime-500
                                        transition
                                        hover:bg-lime-400/10
                                    "
                                >
                                    Create Merchant →
                                </a>

                            </div>

                        @endif

                    </div>

                </div>

            @endif

        </div>

    @endif


    <!-- ========================================================= -->
    <!-- USERS TABLE -->
    <!-- ========================================================= -->

    <div
        class="
            overflow-hidden
            rounded-3xl
            border border-[var(--wr-border)]
            wr-panel
        "
    >

        <div class="overflow-x-auto">

            <table class="w-full min-w-[1100px]">

                <thead class="border-b border-[var(--wr-border)] bg-[var(--wr-input)]">

                <tr>

                    <th class="px-6 py-4 text-left text-xs font-black uppercase tracking-[0.14em] wr-muted">
                        User
                    </th>

                    <th class="px-6 py-4 text-left text-xs font-black uppercase tracking-[0.14em] wr-muted">
                        Login
                    </th>

                    <th class="px-6 py-4 text-left text-xs font-black uppercase tracking-[0.14em] wr-muted">
                        Current Role
                    </th>

                    <th class="px-6 py-4 text-center text-xs font-black uppercase tracking-[0.14em] wr-muted">
                        Merchants
                    </th>

                    <th class="px-6 py-4 text-left text-xs font-black uppercase tracking-[0.14em] wr-muted">
                        Change Role
                    </th>

                    <th class="px-6 py-4 text-left text-xs font-black uppercase tracking-[0.14em] wr-muted">
                        Actions
                    </th>

                </tr>

                </thead>


                <tbody class="divide-y divide-[var(--wr-border)]">

                @forelse($users as $user)

                    @php

                        $roleLocked =
                            $user->role === \App\Enums\UserRole::MERCHANT
                            && $user->merchants_count > 0;

                    @endphp


                    <tr
                        wire:key="user-{{ $user->id }}"
                        class="transition hover:bg-[var(--wr-panel-secondary)]"
                    >

                        <!-- USER -->

                        <td class="px-6 py-5">

                            <div class="font-black wr-text">
                                {{ $user->name ?: '—' }}
                            </div>

                            <div class="mt-1 text-xs wr-muted">
                                ID #{{ $user->id }}
                            </div>

                        </td>


                        <!-- LOGIN -->

                        <td class="px-6 py-5">

                            <div class="font-semibold wr-text">
                                {{ $user->email }}
                            </div>

                        </td>


                        <!-- CURRENT ROLE -->

                        <td class="px-6 py-5">

                            @if($user->role === \App\Enums\UserRole::ADMIN)

                                <span
                                    class="
                                            inline-flex
                                            rounded-full
                                            border border-lime-400/30
                                            bg-lime-400/10
                                            px-3 py-1
                                            text-xs
                                            font-black
                                            text-lime-500
                                        "
                                >
                                        {{ $user->role->label() }}
                                    </span>


                            @elseif($user->role === \App\Enums\UserRole::MERCHANT)

                                <span
                                    class="
                                            inline-flex
                                            rounded-full
                                            border border-blue-400/20
                                            bg-blue-400/10
                                            px-3 py-1
                                            text-xs
                                            font-black
                                            text-blue-300
                                        "
                                >
                                        {{ $user->role->label() }}
                                    </span>


                            @else

                                <span
                                    class="
                                            inline-flex
                                            rounded-full
                                            border border-[var(--wr-border)]
                                            bg-white/[0.04]
                                            px-3 py-1
                                            text-xs
                                            font-black
                                            wr-text
                                        "
                                >
                                        {{ $user->role->label() }}
                                    </span>

                            @endif

                        </td>


                        <!-- MERCHANT COUNT -->

                        <td class="px-6 py-5 text-center">

                            @if($user->role === \App\Enums\UserRole::MERCHANT)

                                <span
                                    class="
                                            inline-flex
                                            min-w-10
                                            items-center
                                            justify-center
                                            rounded-lg
                                            px-3 py-2
                                            text-sm
                                            font-black

                                            {{ $user->merchants_count > 0
                                                ? 'bg-lime-400/10 text-lime-500'
                                                : 'bg-white/[0.04] wr-muted'
                                            }}
                                        "
                                >
                                        {{ $user->merchants_count }}
                                    </span>

                            @else

                                <span class="wr-muted">
                                        —
                                    </span>

                            @endif

                        </td>


                        <!-- CHANGE ROLE -->

                        <td class="px-6 py-5">

                            <select
                                wire:change="changeRole({{ $user->id }}, $event.target.value)"
                                @disabled($roleLocked)
                                class="
                                        min-w-[210px]
                                        rounded-xl
                                        border border-[var(--wr-border)]
                                        bg-[var(--wr-input)]
                                        px-4 py-3
                                        text-sm
                                        font-bold
                                        wr-text
                                        outline-none
                                        transition
                                        focus:border-lime-400/50
                                        disabled:cursor-not-allowed
                                        disabled:opacity-40
                                    "
                            >

                                @foreach($roles as $roleValue => $roleLabel)

                                    <option
                                        value="{{ $roleValue }}"
                                        @selected($user->role->value === $roleValue)
                                    >
                                        {{ $roleLabel }}
                                    </option>

                                @endforeach

                            </select>


                            @if($roleLocked)

                                <div class="mt-2 max-w-xs text-xs leading-5 text-amber-400">
                                    Role cannot be changed while this user has
                                    {{ $user->merchants_count }}
                                    attached
                                    {{ $user->merchants_count === 1 ? 'merchant' : 'merchants' }}.
                                </div>

                            @endif


                            @error('role_' . $user->id)

                            <div class="mt-2 max-w-xs text-xs leading-5 text-red-400">
                                {{ $message }}
                            </div>

                            @enderror

                        </td>


                        <!-- ACTIONS -->

                        <td class="px-6 py-5">

                            <div class="flex flex-col items-start gap-2">

                                <button
                                    type="button"
                                    wire:click="generateNewPassword({{ $user->id }})"
                                    wire:confirm="Generate a new password for {{ $user->email }}? The current password will stop working immediately."
                                    class="
                                            rounded-xl
                                            border border-[var(--wr-border)]
                                            px-4 py-2.5
                                            text-sm
                                            font-bold
                                            wr-text
                                            transition
                                            hover:border-lime-400/30
                                            hover:bg-lime-400/10
                                            hover:text-lime-500
                                        "
                                >
                                    New Password
                                </button>


                                @if($user->role === \App\Enums\UserRole::MERCHANT)

                                    <a
                                        href="{{ route('admin.merchants.user', $user->id) }}"
                                        class="
                                                text-sm
                                                font-black
                                                text-lime-500
                                                transition
                                                hover:text-lime-500
                                            "
                                    >
                                        Manage Merchants →
                                    </a>

                                @endif

                            </div>

                        </td>

                    </tr>


                @empty

                    <tr>

                        <td
                            colspan="6"
                            class="px-6 py-16 text-center"
                        >

                            <div class="font-black wr-text">
                                No users found
                            </div>

                            <div class="mt-2 text-sm wr-muted">
                                Try changing your search query.
                            </div>

                        </td>

                    </tr>

                @endforelse

                </tbody>

            </table>

        </div>

    </div>


    <!-- ========================================================= -->
    <!-- NEW PASSWORD MODAL -->
    <!-- ========================================================= -->

    @if($generatedNewPassword)

        <div
            class="
                fixed
                inset-0
                z-[100]
                flex
                items-center
                justify-center
                bg-black/70
                px-4
                backdrop-blur-sm
            "
        >

            <div
                class="
                    w-full
                    max-w-xl
                    rounded-3xl
                    border border-[var(--wr-border)]
                    wr-panel
                    p-8
                    shadow-2xl
                "
            >

                <div class="text-xs font-black uppercase tracking-[0.18em] text-lime-500">
                    Password Reset
                </div>

                <h2 class="mt-3 text-2xl font-black wr-text">
                    New password generated
                </h2>

                <p class="mt-3 text-sm leading-6 wr-muted">
                    The previous password is no longer valid.
                    Copy the new password and send it to the user securely.
                </p>


                <div
                    class="
                        mt-7
                        rounded-2xl
                        border border-[var(--wr-border)]
                        bg-[var(--wr-input)]
                        p-5
                    "
                >

                    <div class="text-xs font-bold uppercase tracking-[0.14em] wr-muted">
                        Login
                    </div>

                    <div class="mt-2 select-all break-all font-mono text-base font-bold wr-text">
                        {{ $passwordUserEmail }}
                    </div>


                    <div class="mt-6 text-xs font-bold uppercase tracking-[0.14em] wr-muted">
                        New Password
                    </div>

                    <div
                        class="
                            mt-2
                            select-all
                            break-all
                            font-mono
                            text-xl
                            font-black
                            text-lime-500
                        "
                    >
                        {{ $generatedNewPassword }}
                    </div>

                </div>


                <div
                    class="
                        mt-5
                        rounded-xl
                        border border-amber-400/20
                        bg-amber-400/[0.05]
                        p-4
                        text-sm
                        leading-6
                        text-amber-300
                    "
                >
                    This password will not be shown again after this window is closed.
                </div>


                <button
                    type="button"
                    wire:click="closeGeneratedPassword"
                    class="
                        mt-6
                        w-full
                        rounded-xl
                        bg-lime-400
                        px-6 py-3.5
                        font-black
                        text-[#07111f]
                        transition
                        hover:bg-lime-300
                    "
                >
                    I have saved the password
                </button>

            </div>

        </div>

    @endif

</div>
