<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', 'wrangle.win')</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @livewireStyles
</head>

<body class="min-h-screen bg-[#06111f] text-white antialiased">

<div
    class="relative flex min-h-screen items-center justify-center overflow-hidden px-4 py-10"
>

    {{-- Background --}}
    <div class="pointer-events-none absolute inset-0 overflow-hidden">

        {{-- Top left green glow --}}
        <div
            class="absolute -left-[230px] -top-[230px]
                   h-[520px] w-[520px]
                   rounded-full
                   border border-blue-400/20
                   bg-lime-400/[0.08]
                   blur-[1px]">
        </div>

        <div
            class="absolute -left-[120px] -top-[120px]
                   h-[360px] w-[360px]
                   rounded-full
                   bg-lime-400/[0.08]
                   blur-[100px]">
        </div>


        {{-- Bottom right blue glow --}}
        <div
            class="absolute -bottom-[280px] -right-[260px]
                   h-[600px] w-[600px]
                   rounded-full
                   border border-blue-500/25">
        </div>

        <div
            class="absolute -bottom-[150px] -right-[140px]
                   h-[420px] w-[420px]
                   rounded-full
                   bg-blue-600/[0.12]
                   blur-[120px]">
        </div>


        {{-- Soft center glow --}}
        <div
            class="absolute left-1/2 top-1/2
                   h-[700px] w-[700px]
                   -translate-x-1/2 -translate-y-1/2
                   rounded-full
                   bg-blue-900/[0.08]
                   blur-[150px]">
        </div>

    </div>


    {{-- Main auth container --}}
    <div class="relative z-10 w-full max-w-[460px]">


        {{-- Logo --}}
        <div class="mb-8 text-center">

            <a
                href="{{ route('home') }}"
                class="inline-block text-[34px] font-black leading-none tracking-tight text-white"
            >
                wrangle<span class="text-lime-400">.win</span>
            </a>


            <div
                class="mt-4 text-[11px] font-bold uppercase tracking-[0.28em] text-slate-500"
            >
                Prediction Market Infrastructure
            </div>

        </div>


        {{-- Auth Card --}}
        <div
            class="
                rounded-[18px]
                border border-[#244260]
                bg-[#0b192a]/95
                px-7 py-8

                shadow-[0_30px_80px_rgba(0,0,0,0.30)]

                backdrop-blur-xl

                sm:px-8
                sm:py-9
            "
        >

            @yield('content')

        </div>


        {{-- Footer --}}
        <div
            class="mt-8 text-center text-xs text-slate-500"
        >

            © {{ date('Y') }} wrangle.win

            <span class="mx-1.5 text-slate-700">
                •
            </span>

            Secure access

        </div>

    </div>

</div>

@livewireScripts

</body>
</html>
