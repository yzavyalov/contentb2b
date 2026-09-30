@extends('layouts.auth')

@section('title', 'Forgot password — wrangle.win')

@section('content')

    <div>
        <h1 class="text-[28px] font-black leading-tight tracking-tight text-white">
            Forgot password?
        </h1>

        <p class="mt-2 text-[15px] leading-6 text-slate-400">
            Enter your email address and we'll send you a password reset link.
        </p>

        @if ($status)
            <div class="mt-6 rounded-xl border border-lime-400/20 bg-lime-400/10 px-4 py-3 text-sm text-lime-300">
                {{ $status }}
            </div>
        @endif

        <form
            method="POST"
            action="{{ route('password.email') }}"
            class="mt-8 space-y-5"
        >
            @csrf

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
                        autofocus
                        autocomplete="email"
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

            <button
                type="submit"
                class="
                group flex w-full items-center justify-center gap-3
                rounded-xl bg-lime-400 px-5 py-3.5
                text-[15px] font-black text-[#06111f]
                transition hover:bg-lime-300
                focus:outline-none
                focus:ring-4 focus:ring-lime-400/20
            "
            >
                Send reset link

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
            <a
                href="{{ route('login') }}"
                class="text-sm font-bold text-lime-400 transition hover:text-lime-300"
            >
                ← Back to sign in
            </a>
        </div>
    </div>

@endsection
