@extends('layouts.auth')

@section('title', 'Reset password — wrangle.win')

@section('content')

    <div>
        <h1 class="text-[28px] font-black leading-tight tracking-tight text-white">
            Reset password
        </h1>

        <p class="mt-2 text-[15px] text-slate-400">
            Create a new password for your account.
        </p>

        <form
            method="POST"
            action="{{ route('password.store') }}"
            class="mt-8 space-y-5"
        >
            @csrf

            <input type="hidden" name="token" value="{{ $token }}">

            <div>
                <label
                    for="email"
                    class="mb-2 block text-sm font-bold text-white"
                >
                    Email address
                </label>

                <input
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email', $email) }}"
                    required
                    autocomplete="email"
                    class="
                    block w-full rounded-xl
                    border border-[#365777]
                    bg-[#10233a]
                    px-4 py-3.5
                    text-sm text-white
                    outline-none transition
                    focus:border-lime-400/70
                    focus:ring-4 focus:ring-lime-400/10
                "
                >

                @error('email')
                <p class="mt-2 text-sm text-red-400">
                    {{ $message }}
                </p>
                @enderror
            </div>

            <div>
                <label
                    for="password"
                    class="mb-2 block text-sm font-bold text-white"
                >
                    New password
                </label>

                <input
                    id="password"
                    type="password"
                    name="password"
                    required
                    autocomplete="new-password"
                    placeholder="Enter new password"
                    class="
                    block w-full rounded-xl
                    border border-[#365777]
                    bg-[#10233a]
                    px-4 py-3.5
                    text-sm text-white
                    outline-none transition
                    placeholder:text-slate-500
                    focus:border-lime-400/70
                    focus:ring-4 focus:ring-lime-400/10
                "
                >

                @error('password')
                <p class="mt-2 text-sm text-red-400">
                    {{ $message }}
                </p>
                @enderror
            </div>

            <div>
                <label
                    for="password_confirmation"
                    class="mb-2 block text-sm font-bold text-white"
                >
                    Confirm new password
                </label>

                <input
                    id="password_confirmation"
                    type="password"
                    name="password_confirmation"
                    required
                    autocomplete="new-password"
                    placeholder="Repeat new password"
                    class="
                    block w-full rounded-xl
                    border border-[#365777]
                    bg-[#10233a]
                    px-4 py-3.5
                    text-sm text-white
                    outline-none transition
                    placeholder:text-slate-500
                    focus:border-lime-400/70
                    focus:ring-4 focus:ring-lime-400/10
                "
                >
            </div>

            <button
                type="submit"
                class="
                w-full rounded-xl
                bg-lime-400 px-5 py-3.5
                text-[15px] font-black text-[#06111f]
                transition hover:bg-lime-300
                focus:outline-none
                focus:ring-4 focus:ring-lime-400/20
            "
            >
                Reset password
            </button>
        </form>
    </div>

@endsection
