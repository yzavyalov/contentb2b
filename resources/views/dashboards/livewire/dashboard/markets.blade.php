<div class="space-y-6">
    <section class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">Markets</h1>
            <p class="mt-2 text-sm text-slate-500">Browse and manage all markets delivered to your platform.</p>
        </div>

        <div class="flex gap-2">
            <button
                type="button"
                wire:click="resetFilters"
                class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-black text-slate-700 shadow-sm hover:bg-slate-50"
            >
                Reset
            </button>
            <button
                type="button"
                class="rounded-xl bg-lime-400 px-4 py-3 text-sm font-black text-slate-950 shadow-sm hover:bg-lime-300"
            >
                Export
            </button>
        </div>
    </section>

    {{-- Filters --}}
    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-[180px_180px_180px_1fr]">
            <select wire:model.live="category" class="rounded-xl border-slate-200 text-sm focus:border-lime-400 focus:ring-lime-400">
                <option value="">All Categories</option>
                @foreach ($categories as $item)
                    <option value="{{ $item }}">{{ $item }}</option>
                @endforeach
            </select>

            <select wire:model.live="language" class="rounded-xl border-slate-200 text-sm focus:border-lime-400 focus:ring-lime-400">
                <option value="">All Languages</option>
                @foreach ($languages as $item)
                    <option value="{{ $item }}">{{ $item }}</option>
                @endforeach
            </select>

            <select wire:model.live="status" class="rounded-xl border-slate-200 text-sm focus:border-lime-400 focus:ring-lime-400">
                <option value="">All Statuses</option>
                <option value="Active">Active</option>
                <option value="Settled">Settled</option>
            </select>

            <div class="relative">
                <svg viewBox="0 0 24 24" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                     fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="7"/>
                    <path d="m20 20-3.5-3.5"/>
                </svg>

                <input
                    wire:model.live.debounce.300ms="search"
                    type="search"
                    placeholder="Search markets..."
                    class="w-full rounded-xl border-slate-200 pl-10 text-sm focus:border-lime-400 focus:ring-lime-400"
                >
            </div>
        </div>
    </section>

    {{-- Desktop table --}}
    <section class="hidden overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm md:block">
        <div class="overflow-x-auto">
            <table class="min-w-[900px] w-full">
                <thead class="bg-slate-50 text-left text-[11px] font-black uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-4">Market</th>
                        <th class="px-5 py-4">Category</th>
                        <th class="px-5 py-4">Language</th>
                        <th class="px-5 py-4">Status</th>
                        <th class="px-5 py-4">Created At</th>
                        <th class="px-5 py-4 text-right">Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse ($markets as $market)
                        <tr class="hover:bg-slate-50/70">
                            <td class="px-5 py-4">
                                <div class="max-w-[420px] text-sm font-bold text-slate-900">{{ $market['market'] }}</div>
                            </td>
                            <td class="px-5 py-4 text-sm text-slate-600">{{ $market['category'] }}</td>
                            <td class="px-5 py-4 text-sm text-slate-600">{{ $market['language'] }}</td>
                            <td class="px-5 py-4">
                                <span class="rounded-full px-2.5 py-1 text-[10px] font-black
                                    {{ $market['status'] === 'Active' ? 'bg-lime-100 text-emerald-700' : 'bg-blue-100 text-blue-700' }}">
                                    {{ $market['status'] }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-sm text-slate-500">{{ $market['created_at'] }}</td>
                            <td class="px-5 py-4">
                                <div class="flex justify-end gap-2">
                                    <button class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-black text-slate-700 hover:bg-slate-50">
                                        View
                                    </button>
                                    <button class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50">
                                        ⋮
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-16 text-center text-sm text-slate-500">
                                No markets found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{-- Mobile cards --}}
    <section class="space-y-3 md:hidden">
        @forelse ($markets as $market)
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-sm font-black leading-6 text-slate-950">{{ $market['market'] }}</h2>

                        <div class="mt-3 flex flex-wrap gap-2">
                            <span class="rounded-lg bg-slate-100 px-2 py-1 text-[10px] font-black text-slate-600">
                                {{ $market['category'] }}
                            </span>
                            <span class="rounded-lg bg-slate-100 px-2 py-1 text-[10px] font-black text-slate-600">
                                {{ $market['language'] }}
                            </span>
                        </div>
                    </div>

                    <span class="shrink-0 rounded-full px-2.5 py-1 text-[10px] font-black
                        {{ $market['status'] === 'Active' ? 'bg-lime-100 text-emerald-700' : 'bg-blue-100 text-blue-700' }}">
                        {{ $market['status'] }}
                    </span>
                </div>

                <div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-4">
                    <span class="text-xs text-slate-400">{{ $market['created_at'] }}</span>
                    <button class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-black text-slate-700">
                        View
                    </button>
                </div>
            </article>
        @empty
            <div class="rounded-2xl border border-slate-200 bg-white p-10 text-center text-sm text-slate-500">
                No markets found.
            </div>
        @endforelse
    </section>

    <div>
        {{ $markets->links() }}
    </div>
</div>
