@extends('dashboards.layouts.dashboard')

@section('title', 'Password')

@section('content')

    <div class="mx-auto max-w-4xl">

        {{-- Page header --}}
        <div class="mb-8">

            <h1 class="text-3xl font-black tracking-tight text-white">
                Password
            </h1>

            <p class="mt-2 text-sm text-slate-400">
                Update the password used to access your account.
            </p>

        </div>


        {{-- Success message --}}
        @if (session('status') === 'password-updated')

            <div
                class="
                mb-6
                flex items-center gap-3
                rounded-xl
                border border-lime-400/20
                bg-lime-400/10
                px-4 py-3
                text-sm
                text-lime-300
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

                Password updated successfully.

            </div>

        @endif


        <div
            class="
            rounded-2xl
            border border-[#244260]
            bg-[#0b192a]
            p-6
            shadow-xl
            shadow-black/10
            sm:p-8
        "
        >

            <div class="mb-7">

                <h2 class="text-lg font-black text-white">
                    Change password
                </h2>

                <p class="mt-1 text-sm leading-6 text-slate-400">
                    Use a strong password that you don't use on other websites.
                </p>

            </div>


            <form
                method="POST"
                action="{{ route('password.update') }}"
                class="space-y-6"
            >

                @csrf
                @method('PUT')


                {{-- Current password --}}
                <div>

                    <label
                        for="current_password"
                        class="mb-2 block text-sm font-bold text-white"
                    >
                        Current password
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
                                    x="5"
                                    y="10"
                                    width="14"
                                    height="11"
                                    rx="2"
                                />

                                <path d="M8 10V7a4 4 0 0 1 8 0v3"/>
                            </svg>

                        </div>


                        <input
                            id="current_password"
                            type="password"
                            name="current_password"
                            required
                            autocomplete="current-password"
                            placeholder="Enter current password"

                            class="
                            block w-full
                            rounded-xl
                            border border-[#365777]
                            bg-[#10233a]
                            py-3.5
                            pl-12
                            pr-4
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


                    @error('current_password')

                    <p class="mt-2 text-sm text-red-400">
                        {{ $message }}
                    </p>

                    @enderror

                </div>


                {{-- New password --}}
                <div>

                    <label
                        for="password"
                        class="mb-2 block text-sm font-bold text-white"
                    >
                        New password
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
                                    x="5"
                                    y="10"
                                    width="14"
                                    height="11"
                                    rx="2"
                                />

                                <path d="M8 10V7a4 4 0 0 1 8 0v3"/>
                            </svg>

                        </div>


                        <input
                            id="password"
                            type="password"
                            name="password"
                            required
                            autocomplete="new-password"
                            placeholder="Enter new password"

                            class="
                            block w-full
                            rounded-xl
                            border border-[#365777]
                            bg-[#10233a]
                            py-3.5
                            pl-12
                            pr-4
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


                {{-- Confirm password --}}
                <div>

                    <label
                        for="password_confirmation"
                        class="mb-2 block text-sm font-bold text-white"
                    >
                        Confirm new password
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
                                    x="5"
                                    y="10"
                                    width="14"
                                    height="11"
                                    rx="2"
                                />

                                <path d="M8 10V7a4 4 0 0 1 8 0v3"/>
                            </svg>

                        </div>


                        <input
                            id="password_confirmation"
                            type="password"
                            name="password_confirmation"
                            required
                            autocomplete="new-password"
                            placeholder="Repeat new password"

                            class="
                            block w-full
                            rounded-xl
                            border border-[#365777]
                            bg-[#10233a]
                            py-3.5
                            pl-12
                            pr-4
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

                </div>


                {{-- Actions --}}
                <div
                    class="
                    flex items-center justify-end
                    gap-3
                    border-t border-[#213851]
                    pt-6
                "
                >

                    <a
                        href="{{ route('profile.edit') }}"
                        class="
                        rounded-xl
                        border border-[#365777]
                        bg-[#10233a]
                        px-5 py-3
                        text-sm font-bold
                        text-slate-300
                        transition
                        hover:border-[#4b6f91]
                        hover:text-white
                    "
                    >
                        Cancel
                    </a>


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
                        Update password
                    </button>

                </div>

            </form>

        </div>

    </div>

@endsection
