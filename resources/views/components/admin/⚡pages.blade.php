<?php

use App\Models\Page;
use Illuminate\Support\Str;
use Livewire\Component;

new class extends Component
{
    public string $search = '';

    public ?int $editingId = null;

    public bool $showForm = false;

    public string $title = '';
    public string $alias = '';
    public string $content = '';
    public string $meta_title = '';
    public string $meta_description = '';

    public bool $is_active = true;

    public int $sort_order = 0;


    public function updatedTitle(): void
    {
        if (! $this->editingId) {
            $this->alias = Str::slug($this->title);
        }
    }


    public function create(): void
    {
        $this->resetForm();

        $this->showForm = true;
    }


    public function edit(int $id): void
    {
        $page = Page::findOrFail($id);

        $this->editingId = $page->id;

        $this->title = $page->title;
        $this->alias = $page->alias;
        $this->content = $page->content ?? '';

        $this->meta_title = $page->meta_title ?? '';
        $this->meta_description = $page->meta_description ?? '';

        $this->is_active = (bool) $page->is_active;
        $this->sort_order = (int) $page->sort_order;

        $this->showForm = true;

        $this->resetValidation();

        $this->dispatch('page-form-opened');
    }


    public function save(): void
    {
        $validated = $this->validate([
            'title' => [
                'required',
                'string',
                'max:255',
            ],

            'alias' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9\-\/]+$/',
                'unique:pages,alias,' . ($this->editingId ?? 'NULL'),
            ],

            'content' => [
                'nullable',
                'string',
            ],

            'meta_title' => [
                'nullable',
                'string',
                'max:255',
            ],

            'meta_description' => [
                'nullable',
                'string',
            ],

            'is_active' => [
                'boolean',
            ],

            'sort_order' => [
                'integer',
                'min:0',
            ],
        ]);


        if ($this->editingId) {

            $page = Page::findOrFail($this->editingId);

            $page->update($validated);

            session()->flash(
                'status',
                'Page updated successfully.'
            );

        } else {

            Page::create($validated);

            session()->flash(
                'status',
                'Page created successfully.'
            );
        }


        $this->resetForm();

        $this->showForm = false;
    }


    public function delete(int $id): void
    {
        Page::findOrFail($id)->delete();

        session()->flash(
            'status',
            'Page deleted successfully.'
        );
    }


    public function cancel(): void
    {
        $this->resetForm();

        $this->showForm = false;
    }


    protected function resetForm(): void
    {
        $this->reset([
            'editingId',
            'title',
            'alias',
            'content',
            'meta_title',
            'meta_description',
        ]);

        $this->is_active = true;
        $this->sort_order = 0;

        $this->resetValidation();
    }


    public function with(): array
    {
        return [
            'pages' => Page::query()
                ->when(
                    $this->search,
                    function ($query) {
                        $query->where(function ($query) {
                            $query
                                ->where(
                                    'title',
                                    'like',
                                    '%' . $this->search . '%'
                                )
                                ->orWhere(
                                    'alias',
                                    'like',
                                    '%' . $this->search . '%'
                                );
                        });
                    }
                )
                ->orderBy('sort_order')
                ->orderBy('title')
                ->get(),
        ];
    }
};

?>


<div class="mx-auto max-w-7xl">

    <div class="mb-8 flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">

        <div>

            <h1 class="text-3xl font-black tracking-tight wr-text">
                Pages
            </h1>

            <p class="mt-2 text-sm wr-muted">
                Create and manage public website pages.
            </p>

        </div>


        <button
            type="button"
            wire:click="create"
            class="
                inline-flex items-center justify-center gap-2
                rounded-xl
                bg-lime-400
                px-5 py-3
                text-sm font-black
                text-[#06111f]
                transition
                hover:bg-lime-300
            "
        >
            <svg
                class="h-5 w-5"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
            >
                <path d="M12 5v14"/>
                <path d="M5 12h14"/>
            </svg>

            Create page
        </button>

    </div>


    @if (session()->has('status'))

        <div
            class="
                mb-6
                rounded-xl
                border border-lime-400/20
                bg-lime-400/10
                px-4 py-3
                text-sm
                text-lime-500
            "
        >
            {{ session('status') }}
        </div>

    @endif


    @if ($showForm)

        <div
            id="page-editor"
            class="
                mb-8
                rounded-2xl
                border border-[var(--wr-border)]
                wr-panel
                p-6
                sm:p-8
            "
        >

            <h2 class="text-xl font-black wr-text">
                {{ $editingId ? 'Edit page' : 'Create page' }}
            </h2>


            <form
                wire:submit="save"
                class="mt-7 space-y-6"
            >

                <div class="grid gap-6 md:grid-cols-2">

                    <div>

                        <label class="mb-2 block text-sm font-bold wr-text">
                            Page title
                        </label>

                        <input
                            type="text"
                            wire:model.live.debounce.500ms="title"
                            placeholder="Privacy Policy"
                            class="
                                block w-full
                                rounded-xl
                                border border-[var(--wr-border)]
                                bg-[var(--wr-input)]
                                px-4 py-3.5
                                text-sm wr-text
                                outline-none
                                placeholder:wr-muted
                                focus:border-lime-400/70
                                focus:ring-4
                                focus:ring-lime-400/10
                            "
                        >

                        @error('title')
                        <p class="mt-2 text-sm text-red-400">
                            {{ $message }}
                        </p>
                        @enderror

                    </div>


                    <div>

                        <label class="mb-2 block text-sm font-bold wr-text">
                            Alias
                        </label>

                        <div class="flex">

                            <div
                                class="
                                    flex items-center
                                    rounded-l-xl
                                    border border-r-0 border-[var(--wr-border)]
                                    bg-[var(--wr-panel-secondary)]
                                    px-3
                                    text-sm wr-muted
                                "
                            >
                                /
                            </div>

                            <input
                                type="text"
                                wire:model="alias"
                                placeholder="privacy-policy"
                                class="
                                    block w-full
                                    rounded-r-xl
                                    border border-[var(--wr-border)]
                                    bg-[var(--wr-input)]
                                    px-4 py-3.5
                                    text-sm wr-text
                                    outline-none
                                    placeholder:wr-muted
                                    focus:border-lime-400/70
                                "
                            >

                        </div>

                        @error('alias')
                        <p class="mt-2 text-sm text-red-400">
                            {{ $message }}
                        </p>
                        @enderror

                    </div>

                </div>


                <div>

                    <label class="mb-2 block text-sm font-bold wr-text">
                        Page content
                    </label>

                    <textarea
                        wire:model="content"
                        rows="14"
                        placeholder="<h2>Page content</h2>"
                        class="
                            block w-full
                            rounded-xl
                            border border-[var(--wr-border)]
                            bg-[var(--wr-input)]
                            px-4 py-4
                            font-mono
                            text-sm leading-6
                            wr-text
                            outline-none
                            placeholder:wr-muted
                            focus:border-lime-400/70
                            focus:ring-4
                            focus:ring-lime-400/10
                        "
                    ></textarea>

                </div>


                <div
                    class="
                        rounded-xl
                        border border-[var(--wr-border)]
                        bg-[var(--wr-panel-secondary)]
                        p-5
                    "
                >

                    <h3 class="text-sm font-black wr-text">
                        SEO
                    </h3>


                    <div class="mt-5 space-y-5">

                        <div>

                            <label class="mb-2 block text-sm font-bold wr-text">
                                Meta title
                            </label>

                            <input
                                type="text"
                                wire:model="meta_title"
                                class="
                                    block w-full
                                    rounded-xl
                                    border border-[var(--wr-border)]
                                    bg-[var(--wr-input)]
                                    px-4 py-3.5
                                    text-sm wr-text
                                    outline-none
                                    focus:border-lime-400/70
                                "
                            >

                        </div>


                        <div>

                            <label class="mb-2 block text-sm font-bold wr-text">
                                Meta description
                            </label>

                            <textarea
                                wire:model="meta_description"
                                rows="3"
                                class="
                                    block w-full
                                    rounded-xl
                                    border border-[var(--wr-border)]
                                    bg-[var(--wr-input)]
                                    px-4 py-3.5
                                    text-sm wr-text
                                    outline-none
                                    focus:border-lime-400/70
                                "
                            ></textarea>

                        </div>

                    </div>

                </div>


                <div class="grid gap-6 sm:grid-cols-2">

                    <div>

                        <label class="mb-2 block text-sm font-bold wr-text">
                            Sort order
                        </label>

                        <input
                            type="number"
                            wire:model="sort_order"
                            min="0"
                            class="
                                block w-full
                                rounded-xl
                                border border-[var(--wr-border)]
                                bg-[var(--wr-input)]
                                px-4 py-3.5
                                text-sm wr-text
                                outline-none
                            "
                        >

                    </div>


                    <div class="flex items-end">

                        <label
                            class="
                                flex w-full
                                cursor-pointer
                                items-center gap-3
                                rounded-xl
                                border border-[var(--wr-border)]
                                bg-[var(--wr-input)]
                                px-4 py-3.5
                            "
                        >

                            <input
                                type="checkbox"
                                wire:model="is_active"
                            >

                            <div>

                                <div class="text-sm font-bold wr-text">
                                    Published
                                </div>

                                <div class="text-xs wr-muted">
                                    Publicly accessible
                                </div>

                            </div>

                        </label>

                    </div>

                </div>


                <div
                    class="
                        flex justify-end gap-3
                        border-t border-[var(--wr-border)]
                        pt-6
                    "
                >

                    <button
                        type="button"
                        wire:click="cancel"
                        class="
                            rounded-xl
                            border border-[var(--wr-border)]
                            bg-[var(--wr-input)]
                            px-5 py-3
                            text-sm font-bold
                            wr-text
                        "
                    >
                        Cancel
                    </button>


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
                        "
                    >
                        {{ $editingId ? 'Save changes' : 'Create page' }}
                    </button>

                </div>

            </form>

        </div>

    @endif


    <div
        class="
            overflow-hidden
            rounded-2xl
            border border-[var(--wr-border)]
            wr-panel
        "
    >

        <div class="border-b border-[var(--wr-border)] p-5">

            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="Search pages..."
                class="
                    block w-full max-w-md
                    rounded-xl
                    border border-[var(--wr-border)]
                    bg-[var(--wr-input)]
                    px-4 py-3
                    text-sm wr-text
                    outline-none
                    placeholder:wr-muted
                    focus:border-lime-400/70
                "
            >

        </div>


        <div class="overflow-x-auto">

            <table class="w-full">

                <thead>
                <tr
                    class="
                            border-b border-[var(--wr-border)]
                            text-left text-xs
                            font-bold uppercase
                            tracking-[0.12em]
                            wr-muted
                        "
                >
                    <th class="px-6 py-4">Page</th>
                    <th class="px-6 py-4">URL</th>
                    <th class="px-6 py-4">Status</th>
                    <th class="px-6 py-4">Updated</th>
                    <th class="px-6 py-4 text-right">Actions</th>
                </tr>
                </thead>


                <tbody class="divide-y divide-[var(--wr-border)]">

                @forelse($pages as $page)

                    <tr class="transition hover:bg-[var(--wr-input)]/50">

                        <td class="px-6 py-5">

                            <div class="font-bold wr-text">
                                {{ $page->title }}
                            </div>

                            @if($page->meta_title)
                                <div class="mt-1 text-xs wr-muted">
                                    {{ $page->meta_title }}
                                </div>
                            @endif

                        </td>


                        <td class="px-6 py-5">

                            <a
                                href="/{{ $page->alias }}"
                                target="_blank"
                                class="text-sm font-semibold text-lime-500"
                            >
                                /{{ $page->alias }}
                            </a>

                        </td>


                        <td class="px-6 py-5">

                            @if($page->is_active)

                                <span
                                    class="
                                            rounded-full
                                            border border-lime-400/20
                                            bg-lime-400/10
                                            px-3 py-1
                                            text-xs font-bold
                                            text-lime-500
                                        "
                                >
                                        Published
                                    </span>

                            @else

                                <span
                                    class="
                                            rounded-full
                                            bg-[var(--wr-panel-secondary)]
                                            px-3 py-1
                                            text-xs font-bold
                                            wr-muted
                                        "
                                >
                                        Draft
                                    </span>

                            @endif

                        </td>


                        <td class="px-6 py-5 text-sm wr-muted">
                            {{ $page->updated_at->format('d M Y H:i') }}
                        </td>


                        <td class="px-6 py-5">

                            <div class="flex justify-end gap-2">

                                <button
                                    type="button"
                                    wire:click="edit({{ $page->id }})"
                                    class="
                                            rounded-lg
                                            border border-[var(--wr-border)]
                                            bg-[var(--wr-input)]
                                            px-3 py-2
                                            text-xs font-bold
                                            wr-text
                                        "
                                >
                                    Edit
                                </button>


                                <button
                                    type="button"
                                    wire:click="delete({{ $page->id }})"
                                    wire:confirm="Are you sure you want to delete this page?"
                                    class="
                                            rounded-lg
                                            border border-red-500/20
                                            bg-red-500/10
                                            px-3 py-2
                                            text-xs font-bold
                                            text-red-300
                                        "
                                >
                                    Delete
                                </button>

                            </div>

                        </td>

                    </tr>

                @empty

                    <tr>
                        <td
                            colspan="5"
                            class="px-6 py-16 text-center wr-muted"
                        >
                            No pages found.
                        </td>
                    </tr>

                @endforelse

                </tbody>

            </table>

        </div>

    </div>

</div>

@script
<script>
    $wire.on('page-form-opened', () => {
        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                const editor = document.getElementById('page-editor');

                if (editor) {
                    editor.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    });
</script>
@endscript
