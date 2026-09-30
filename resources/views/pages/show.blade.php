@extends('layouts.public')

@section('title', $page->meta_title ?: $page->title)

@section('meta_description', $page->meta_description ?? '')

@section('content')

    <section class="relative overflow-hidden bg-[#07111f] px-6 py-20 sm:py-28">

        <div class="pointer-events-none absolute inset-0">

            <div
                class="absolute left-1/2 top-[-300px] h-[700px] w-[700px] -translate-x-1/2 rounded-full bg-lime-400/[0.06] blur-[130px]"
            ></div>

            <div
                class="absolute bottom-[-300px] right-[-200px] h-[600px] w-[600px] rounded-full bg-blue-500/[0.05] blur-[120px]"
            ></div>

        </div>


        <div class="relative mx-auto max-w-6xl">

            <div class="mb-5 text-xs font-black uppercase tracking-[0.2em] text-lime-400">
                wrangle.win
            </div>

            <h1 class="max-w-4xl text-4xl font-black leading-tight tracking-tight text-white sm:text-6xl">
                {{ $page->title }}
            </h1>

            @if($page->meta_description)

                <p class="mt-7 max-w-3xl text-lg leading-8 text-slate-400">
                    {{ $page->meta_description }}
                </p>

            @endif

        </div>

    </section>


    <section class="bg-[#07111f] px-6 pb-24">

        <div class="mx-auto max-w-6xl">

            <article>
                {!! $page->content !!}
            </article>

        </div>

    </section>

@endsection
