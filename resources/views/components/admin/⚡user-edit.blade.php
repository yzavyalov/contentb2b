<?php

use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Component;

new class extends Component
{
    public int $userId;

    public string $name = '';

    public string $email = '';

    public string $contact = '';

    public function mount(int $userId): void
    {
        $this->userId = $userId;

        $user = User::findOrFail($this->userId);

        $this->name = $user->name ?? '';
        $this->email = $user->email ?? '';
        $this->contact = $user->contact ?? '';
    }

    public function save(): void
    {
        $user = User::findOrFail($this->userId);

        $this->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],

            'contact' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ]);

        $user->update([
            'name' => trim($this->name),
            'email' => strtolower(trim($this->email)),
            'contact' => trim($this->contact) !== ''
                ? trim($this->contact)
                : null,
        ]);

        $this->contact = $user->contact ?? '';

        session()->flash(
            'status',
            'User updated successfully.'
        );
    }

    public function with(): array
    {
        return [
            'user' => User::findOrFail($this->userId),
        ];
    }
};
?>

<div>

    <div class="mb-8">
        <div class="text-xs font-black uppercase tracking-[0.18em] text-lime-500">
            Administration
        </div>

        <h1 class="mt-2 text-3xl font-black tracking-tight wr-text">
            Edit User
        </h1>

        <p class="mt-2 text-sm leading-6 wr-muted">
            {{ $user->email }}
        </p>
    </div>

    @if(session('status'))
        <div
            class="mb-6 rounded-2xl border border-lime-400/30 bg-lime-400/[0.06] px-5 py-4 text-sm font-bold text-lime-500"
        >
            {{ session('status') }}
        </div>
    @endif

    <div
        class="rounded-3xl border border-[var(--wr-border)] wr-panel p-7 sm:p-8"
    >
        <form wire:submit="save">

            <div class="grid gap-5 lg:grid-cols-2">

                <!-- NAME -->

                <div>
                    <label class="mb-2 block text-sm font-bold wr-text">
                        Name
                    </label>

                    <input
                        type="text"
                        wire:model="name"
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

                    @error('name')
                    <div class="mt-2 text-xs font-bold text-red-400">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                <!-- EMAIL -->

                <div>
                    <label class="mb-2 block text-sm font-bold wr-text">
                        Email
                    </label>

                    <input
                        type="email"
                        wire:model="email"
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

                    @error('email')
                    <div class="mt-2 text-xs font-bold text-red-400">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                <!-- CONTACT -->

                <div class="lg:col-span-2">
                    <label class="mb-2 block text-sm font-bold wr-text">
                        Contact
                        <span class="ml-1 text-xs font-normal wr-muted">
                            (optional)
                        </span>
                    </label>

                    <textarea
                        wire:model="contact"
                        rows="4"
                        placeholder="Telegram, WhatsApp, phone or other contact details..."
                        class="
                            w-full
                            resize-y
                            rounded-xl
                            border border-[var(--wr-border)]
                            bg-[var(--wr-input)]
                            px-4 py-3.5
                            wr-text
                            outline-none
                            placeholder:wr-muted
                            focus:border-lime-400/50
                        "
                    ></textarea>

                    @error('contact')
                    <div class="mt-2 text-xs font-bold text-red-400">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

            </div>

            <div class="mt-7 flex flex-wrap gap-3">

                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="save"
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
                    <span wire:loading.remove wire:target="save">
                        Save Changes
                    </span>

                    <span wire:loading wire:target="save">
                        Saving...
                    </span>
                </button>

                <a
                    href="{{ route('admin.users.index') }}"
                    class="
                        inline-flex
                        items-center
                        rounded-xl
                        border border-[var(--wr-border)]
                        px-6 py-3.5
                        font-bold
                        wr-text
                        transition
                        hover:border-lime-400/30
                    "
                >
                    Back to Users
                </a>

            </div>

        </form>
    </div>

</div>
