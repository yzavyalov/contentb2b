@extends('layouts.auth')

@section('title', 'Verify email — wrangle.win')

@section('content')

    <div>
        <h1 class="text-[28px] font-black leading-tight tracking-tight text-white">
            Verify your email
        </h1>

        <p class="mt-3 text-[15px] leading-6 text-slate-400">
            We've sent a verification link to your email address.
            Open the email and click the link to activate your account.
        </p>

        @if ($status === 'verification-link-sent')
            <div class="mt-6 rounded-xl border border-lime-400/20 bg-lime-400/10 px-4 py-3 text-sm text-lime-300">
                A new verification link has been sent.
            </div>
        @endif

        <form
            method="POST"
            action="{{ route('verification.send') }}"
            class="mt-8"
        >
            @csrf

            <button
                type="submit"
                class="
                w-full rounded-xl
                bg-lime-400 px-5 py-3.5
                text-[15px] font-black text-[#06111f]
                transition hover:bg-lime-300
            "
            >
                Resend verification email
            </button>
        </form>

        <form
            method="POST"
            action="{{ route('logout') }}"
            class="mt-4"
        >
            @csrf

            <button
                type="submit"
                class="
                w-full rounded-xl
                border border-[#365777]
                bg-[#10233a]
                px-5 py-3.5
                text-sm font-bold text-slate-200
                transition
                hover:border-[#4d6e90]
                hover:bg-[#132944]
            "
            >
                Sign out
            </button>
        </form>
    </div>

@endsection
