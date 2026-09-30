@extends('layouts.auth')

@section('title', 'Sign in — wrangle.win')

@section('content')

    <div>

        {{-- Header --}}
        <div>

            <h1
                class="text-[28px] font-black leading-tight tracking-tight text-white"
            >
                Welcome back
            </h1>

            <p class="mt-2 text-[15px] text-slate-400">
                Sign in to your wrangle.win account.
            </p>

        </div>


        {{-- Status --}}
        @if ($status)

            <div
                class="
                mt-6
                rounded-xl
                border border-lime-400/20
                bg-lime-400/10
                px-4 py-3
                text-sm
                text-lime-300
            "
            >
                {{ $status }}
            </div>

        @endif


        <form
            method="POST"
            action="{{ route('login') }}"
            class="mt-8 space-y-5"
        >

            @csrf


            {{-- Email --}}
            <div>

                <label
                    for="email"
                    class="mb-2 block text-sm font-bold text-white"
                >
                    Email address
                </label>


                <div class="relative">

                    {{-- Email icon --}}
                    <div
                        class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400"
                    >

                        <svg
                            class="h-5 w-5"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.7"
                        >
                            <rect
                                x="3"
                                y="5"
                                width="18"
                                height="14"
                                rx="2"
                            />

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
                        autocomplete="username"
                        placeholder="you@company.com"

                        class="
                        block w-full
                        rounded-xl
                        border border-[#365777]
                        bg-[#10233a]
                        py-3.5
                        pl-12 pr-4

                        text-sm
                        text-white

                        outline-none
                        transition

                        placeholder:text-slate-500

                        focus:border-lime-400/70
                        focus:ring-4
                        focus:ring-lime-400/10
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

                <div class="mb-2 flex items-center justify-between">

                    <label
                        for="password"
                        class="text-sm font-bold text-white"
                    >
                        Password
                    </label>


                    @if ($canResetPassword)

                        <a
                            href="{{ route('password.request') }}"
                            class="
                            text-sm
                            font-medium
                            text-lime-400
                            transition
                            hover:text-lime-300
                        "
                        >
                            Forgot password?
                        </a>

                    @endif

                </div>


                <div class="relative">

                    {{-- Lock icon --}}
                    <div
                        class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400"
                    >

                        <svg
                            class="h-5 w-5"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.7"
                        >

                            <rect
                                x="5"
                                y="10"
                                width="14"
                                height="11"
                                rx="2"
                            />

                            <path
                                d="M8 10V7a4 4 0 0 1 8 0v3"
                            />

                            <path d="M12 14v3"/>

                        </svg>

                    </div>


                    <input
                        id="password"
                        type="password"
                        name="password"
                        required
                        autocomplete="current-password"
                        placeholder="Enter your password"

                        class="
                        block w-full
                        rounded-xl
                        border border-[#365777]
                        bg-[#10233a]
                        py-3.5
                        pl-12 pr-4

                        text-sm
                        text-white

                        outline-none
                        transition

                        placeholder:text-slate-500

                        focus:border-lime-400/70
                        focus:ring-4
                        focus:ring-lime-400/10
                    "
                    >

                </div>


                @error('password')

                <p class="mt-2 text-sm text-red-400">
                    {{ $message }}
                </p>

                @enderror

            </div>


            {{-- Remember --}}
            <div>

                <label
                    for="remember"
                    class="inline-flex cursor-pointer items-center gap-3"
                >

                    <input
                        id="remember"
                        type="checkbox"
                        name="remember"

                        class="
                        h-5 w-5
                        rounded
                        border-[#57728e]
                        bg-[#10233a]
                        text-lime-400

                        focus:ring-2
                        focus:ring-lime-400/30
                        focus:ring-offset-0
                    "
                    >

                    <span class="text-sm text-slate-200">
                    Remember me
                </span>

                </label>

            </div>


            {{-- Login Button --}}
            <button
                type="submit"

                class="
                group
                flex w-full
                items-center
                justify-center
                gap-3

                rounded-xl
                bg-lime-400
                px-5
                py-3.5

                text-[15px]
                font-black
                text-[#06111f]

                shadow-[0_8px_30px_rgba(163,230,53,0.12)]

                transition

                hover:bg-lime-300
                hover:shadow-[0_8px_35px_rgba(163,230,53,0.20)]

                focus:outline-none
                focus:ring-4
                focus:ring-lime-400/20
            "
            >

                Sign in


                <svg
                    class="h-5 w-5 transition-transform group-hover:translate-x-1"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                >

                    <path d="M5 12h14"/>
                    <path d="m13 6 6 6-6 6"/>

                </svg>

            </button>


        </form>


        {{-- Registration --}}
        @if (Route::has('register'))

            <div
                class="
                mt-7
                border-t border-[#213851]
                pt-6
                text-center
            "
            >

                <p class="text-sm text-slate-400">

                    Don't have an account?

                    <a
                        href="{{ route('register') }}"
                        class="
                        ml-1
                        font-bold
                        text-lime-400
                        transition
                        hover:text-lime-300
                    "
                    >
                        Create account
                    </a>

                </p>

            </div>

        @endif

    </div>

@endsection
