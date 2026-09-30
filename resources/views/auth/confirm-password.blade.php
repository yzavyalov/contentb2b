@extends('layouts.auth')

@section('title', 'Confirm password — wrangle.win')

@section('content')

    <div>
        <h1 class="text-[28px] font-black leading-tight tracking-tight text-white">
            Confirm password
        </h1>

        <p class="mt-3 text-[15px] leading-6 text-slate-400">
            This is a secure area. Confirm your password before continuing.
        </p>

        <form
            method="POST"
            action="{{ route('password.confirm') }}"
            class="mt-8 space-y-5"
        >
            @csrf

            <div>
                <label
                    for="password"
                    class="mb-2 block text-sm font-bold text-white"
                >
                    Password
                </label>

                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                            <rect x="5" y="10" width="14" height="11" rx="2"/>
                            <path d="M8 10V7a4 4 0 0 1 8 0v3"/>
                        </svg>
                    </div>

                    <input
                        id="password"
                        type="password"
                        name="password"
                        required
                        autofocus
                        autocomplete="current-password"
                        placeholder="Enter your password"
                        class="
                        block w-full rounded-xl
                        border border-[#365777]
                        bg-[#10233a]
                        py-3.5 pl-12 pr-4
                        text-sm text-white
                        outline-none transition
                        placeholder:text-slate-500
                        focus:border-lime-400/70
                        focus:ring-4 focus:ring-lime-400/10
                    "
                    >
                </div>

                @error('password')
                <p class="mt-2 text-sm text-red-400">
                    {{ $message }}
                </p>
                @enderror
            </div>

            <button
                type="submit"
                class="
                w-full rounded-xl
                bg-lime-400 px-5 py-3.5
                text-[15px] font-black text-[#06111f]
                transition hover:bg-lime-300
            "
            >
                Confirm password
            </button>
        </form>
    </div>

@endsection
