@extends('layouts.auth')

@section('title', 'Create account — wrangle.win')

@section('content')

    <div>
        <div>
            <h1 class="text-[28px] font-black leading-tight tracking-tight text-white">
                Create account
            </h1>

            <p class="mt-2 text-[15px] text-slate-400">
                Start using wrangle.win with your platform.
            </p>
        </div>

        <form
            method="POST"
            action="{{ route('register') }}"
            class="mt-8 space-y-5"
        >
            @csrf

            {{-- Name --}}
            <div>
                <label
                    for="name"
                    class="mb-2 block text-sm font-bold text-white"
                >
                    Name
                </label>

                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                            <circle cx="12" cy="8" r="4"/>
                            <path d="M4 21a8 8 0 0 1 16 0"/>
                        </svg>
                    </div>

                    <input
                        id="name"
                        type="text"
                        name="name"
                        value="{{ old('name') }}"
                        required
                        autofocus
                        autocomplete="name"
                        placeholder="Your name"
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

                @error('name')
                <p class="mt-2 text-sm text-red-400">
                    {{ $message }}
                </p>
                @enderror
            </div>

            {{-- Email --}}
            <div>
                <label
                    for="email"
                    class="mb-2 block text-sm font-bold text-white"
                >
                    Email address
                </label>

                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                            <rect x="3" y="5" width="18" height="14" rx="2"/>
                            <path d="m3 7 9 6 9-6"/>
                        </svg>
                    </div>

                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autocomplete="username"
                        placeholder="you@company.com"
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

                @error('email')
                <p class="mt-2 text-sm text-red-400">
                    {{ $message }}
                </p>
                @enderror
            </div>

            {{-- Password --}}
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
                            <path d="M12 14v3"/>
                        </svg>
                    </div>

                    <input
                        id="password"
                        type="password"
                        name="password"
                        required
                        autocomplete="new-password"
                        placeholder="Create a password"
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

            {{-- Password confirmation --}}
            <div>
                <label
                    for="password_confirmation"
                    class="mb-2 block text-sm font-bold text-white"
                >
                    Confirm password
                </label>

                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                            <rect x="5" y="10" width="14" height="11" rx="2"/>
                            <path d="M8 10V7a4 4 0 0 1 8 0v3"/>
                            <path d="M12 14v3"/>
                        </svg>
                    </div>

                    <input
                        id="password_confirmation"
                        type="password"
                        name="password_confirmation"
                        required
                        autocomplete="new-password"
                        placeholder="Repeat your password"
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
            </div>

            <button
                type="submit"
                class="
                group flex w-full items-center justify-center gap-3
                rounded-xl bg-lime-400 px-5 py-3.5
                text-[15px] font-black text-[#06111f]
                transition
                hover:bg-lime-300
                focus:outline-none
                focus:ring-4 focus:ring-lime-400/20
            "
            >
                Create account

                <svg class="h-5 w-5 transition-transform group-hover:translate-x-1"
                     viewBox="0 0 24 24"
                     fill="none"
                     stroke="currentColor"
                     stroke-width="2">
                    <path d="M5 12h14"/>
                    <path d="m13 6 6 6-6 6"/>
                </svg>
            </button>
        </form>

        <div class="mt-7 border-t border-[#213851] pt-6 text-center">
            <p class="text-sm text-slate-400">
                Already have an account?

                <a
                    href="{{ route('login') }}"
                    class="ml-1 font-bold text-lime-400 transition hover:text-lime-300"
                >
                    Sign in
                </a>
            </p>
        </div>
    </div>

@endsection
