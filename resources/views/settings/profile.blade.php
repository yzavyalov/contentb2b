@extends('dashboards.layouts.dashboard')

@section('title', 'Profile')

@section('content')

    <div class="mx-auto max-w-5xl">

        {{-- Page header --}}
        <div class="mb-8">
            <h1 class="text-3xl font-black tracking-tight text-white">
                Profile
            </h1>

            <p class="mt-2 text-sm leading-6 text-slate-400">
                Manage your personal account information.
            </p>
        </div>


        {{-- Success message --}}
        @if (session('status') === 'profile-updated')
            <div
                class="
                mb-6
                flex items-center gap-3
                rounded-xl
                border border-lime-400/20
                bg-lime-400/10
                px-4 py-3
                text-sm text-lime-300
            "
            >
                <svg
                    class="h-5 w-5 shrink-0"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                >
                    <path d="M20 6 9 17l-5-5"/>
                </svg>

                Profile updated successfully.
            </div>
        @endif


        <div class="grid gap-6 lg:grid-cols-[1fr_280px]">

            {{-- LEFT COLUMN --}}
            <div class="space-y-6">

                {{-- Personal information --}}
                <div
                    class="
                    rounded-2xl
                    border border-[#244260]
                    bg-[#0b192a]
                    p-6
                    shadow-xl shadow-black/10
                    sm:p-8
                "
                >

                    <div class="mb-7">
                        <h2 class="text-lg font-black text-white">
                            Personal information
                        </h2>

                        <p class="mt-1 text-sm text-slate-400">
                            Information associated with your wrangle.win account.
                        </p>
                    </div>


                    <form
                        method="POST"
                        action="{{ route('profile.update') }}"
                        class="space-y-6"
                    >
                        @csrf
                        @method('PATCH')


                        {{-- Name --}}
                        <div>
                            <label
                                for="name"
                                class="mb-2 block text-sm font-bold text-white"
                            >
                                Full name
                            </label>

                            <div class="relative">

                                <div
                                    class="
                                    pointer-events-none
                                    absolute inset-y-0 left-0
                                    flex items-center
                                    pl-4
                                    text-slate-400
                                "
                                >
                                    <svg
                                        class="h-5 w-5"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.7"
                                    >
                                        <circle cx="12" cy="8" r="4"/>
                                        <path d="M4 21a8 8 0 0 1 16 0"/>
                                    </svg>
                                </div>

                                <input
                                    id="name"
                                    type="text"
                                    name="name"
                                    value="{{ old('name', $user->name) }}"
                                    required
                                    autocomplete="name"
                                    placeholder="Your name"

                                    class="
                                    block w-full
                                    rounded-xl
                                    border border-[#365777]
                                    bg-[#10233a]
                                    py-3.5
                                    pl-12 pr-4
                                    text-sm text-white
                                    outline-none
                                    transition
                                    placeholder:text-slate-500
                                    focus:border-lime-400/70
                                    focus:ring-4
                                    focus:ring-lime-400/10
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

                                <div
                                    class="
                                    pointer-events-none
                                    absolute inset-y-0 left-0
                                    flex items-center
                                    pl-4
                                    text-slate-400
                                "
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
                                    value="{{ old('email', $user->email) }}"
                                    required
                                    autocomplete="username"
                                    placeholder="you@company.com"

                                    class="
                                    block w-full
                                    rounded-xl
                                    border border-[#365777]
                                    bg-[#10233a]
                                    py-3.5
                                    pl-12 pr-4
                                    text-sm text-white
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


                        {{-- Email verification --}}
                        @if ($mustVerifyEmail && ! $user->hasVerifiedEmail())

                            <div
                                class="
                                rounded-xl
                                border border-amber-400/20
                                bg-amber-400/10
                                px-4 py-4
                            "
                            >
                                <div class="flex gap-3">

                                    <svg
                                        class="mt-0.5 h-5 w-5 shrink-0 text-amber-300"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                    >
                                        <circle cx="12" cy="12" r="9"/>
                                        <path d="M12 8v5"/>
                                        <path d="M12 16h.01"/>
                                    </svg>

                                    <div>
                                        <div class="text-sm font-bold text-amber-200">
                                            Email not verified
                                        </div>

                                        <p class="mt-1 text-sm text-amber-200/70">
                                            Please verify your email address to secure your account.
                                        </p>
                                    </div>

                                </div>
                            </div>

                        @endif


                        {{-- Save --}}
                        <div
                            class="
                            flex items-center justify-end
                            border-t border-[#213851]
                            pt-6
                        "
                        >
                            <button
                                type="submit"
                                class="
                                rounded-xl
                                bg-lime-400
                                px-6 py-3
                                text-sm font-black
                                text-[#06111f]
                                transition
                                hover:bg-lime-300
                                focus:outline-none
                                focus:ring-4
                                focus:ring-lime-400/20
                            "
                            >
                                Save changes
                            </button>
                        </div>

                    </form>

                </div>


                {{-- Security --}}
                <div
                    class="
                    rounded-2xl
                    border border-[#244260]
                    bg-[#0b192a]
                    p-6
                    sm:p-8
                "
                >

                    <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">

                        <div>
                            <h2 class="text-lg font-black text-white">
                                Password
                            </h2>

                            <p class="mt-1 text-sm text-slate-400">
                                Change the password used to sign in to your account.
                            </p>
                        </div>

                        <a
                            href="{{ route('password.edit') }}"
                            class="
                            inline-flex shrink-0 items-center justify-center
                            rounded-xl
                            border border-[#365777]
                            bg-[#10233a]
                            px-5 py-3
                            text-sm font-bold
                            text-slate-200
                            transition
                            hover:border-[#4d7193]
                            hover:text-white
                        "
                        >
                            Change password
                        </a>

                    </div>

                </div>

            </div>


            {{-- RIGHT COLUMN --}}
            <div class="space-y-6">

                {{-- Account --}}
                <div
                    class="
                    rounded-2xl
                    border border-[#244260]
                    bg-[#0b192a]
                    p-6
                "
                >

                    <div class="mb-5 text-xs font-bold uppercase tracking-[0.16em] text-slate-500">
                        Account
                    </div>


                    {{-- Avatar --}}
                    <div
                        class="
                        flex h-14 w-14
                        items-center justify-center
                        rounded-2xl
                        bg-lime-400
                        text-xl font-black
                        uppercase
                        text-[#06111f]
                    "
                    >
                        {{ mb_substr($user->name, 0, 1) }}
                    </div>


                    <div class="mt-4">
                        <div class="font-bold text-white">
                            {{ $user->name }}
                        </div>

                        <div class="mt-1 break-all text-sm text-slate-500">
                            {{ $user->email }}
                        </div>
                    </div>


                    {{-- Role --}}
                    @if($user->role)

                        <div class="mt-6 border-t border-[#213851] pt-5">

                            <div class="text-xs text-slate-500">
                                System role
                            </div>

                            <div
                                class="
                                mt-2 inline-flex
                                rounded-full
                                border border-lime-400/20
                                bg-lime-400/10
                                px-3 py-1.5
                                text-xs font-bold
                                text-lime-300
                            "
                            >
                                {{ $user->role->label() }}
                            </div>

                        </div>

                    @endif

                </div>


                {{-- Merchants --}}
                <div
                    class="
                    rounded-2xl
                    border border-[#244260]
                    bg-[#0b192a]
                    p-6
                "
                >

                    <div class="flex items-center gap-3">

                        <div
                            class="
                            flex h-10 w-10
                            items-center justify-center
                            rounded-xl
                            bg-[#10233a]
                            text-lime-400
                        "
                        >
                            <svg
                                class="h-5 w-5"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >
                                <path d="M4 21V7l8-4 8 4v14"/>
                                <path d="M8 10h2"/>
                                <path d="M14 10h2"/>
                                <path d="M8 14h2"/>
                                <path d="M14 14h2"/>
                                <path d="M9 21v-3h6v3"/>
                            </svg>
                        </div>

                        <div>
                            <div class="font-bold text-white">
                                Merchants
                            </div>

                            <div class="text-xs text-slate-500">
                                Your organizations
                            </div>
                        </div>

                    </div>

                    <p class="mt-4 text-sm leading-6 text-slate-400">
                        Create and manage companies connected to your account.
                    </p>

                    {{-- Пока Merchant модель еще не создана --}}
                    <div
                        class="
                        mt-5 rounded-xl
                        border border-dashed border-[#365777]
                        px-4 py-4
                        text-center
                    "
                    >
                        <div class="text-sm font-semibold text-slate-300">
                            Merchant management
                        </div>

                        <div class="mt-1 text-xs text-slate-500">
                            Will be available here
                        </div>
                    </div>

                </div>

            </div>

        </div>

    </div>

@endsection
