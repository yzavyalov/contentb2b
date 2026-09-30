@extends('dashboards.layouts.dashboard')

@section('content')

    <div class="space-y-6">

        <div>
            <h1 class="text-2xl font-semibold wr-text">
                Financial Dashboard
            </h1>

            <p class="mt-1 text-sm wr-muted">
                Manage merchant billing, plans, balances and revenue.
            </p>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">

            <div class="wr-panel rounded-xl border p-5">
                <div class="text-sm wr-muted">Revenue</div>
                <div class="mt-2 text-2xl font-semibold wr-text">—</div>
            </div>

            <div class="wr-panel rounded-xl border p-5">
                <div class="text-sm wr-muted">Merchants</div>
                <div class="mt-2 text-2xl font-semibold wr-text">—</div>
            </div>

            <div class="wr-panel rounded-xl border p-5">
                <div class="text-sm wr-muted">Active subscriptions</div>
                <div class="mt-2 text-2xl font-semibold wr-text">—</div>
            </div>

            <div class="wr-panel rounded-xl border p-5">
                <div class="text-sm wr-muted">Markets delivered</div>
                <div class="mt-2 text-2xl font-semibold wr-text">—</div>
            </div>

        </div>

    </div>

@endsection
