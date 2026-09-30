<?php

use App\Models\BetTranslation;
use App\Models\Category;
use App\Models\Country;
use App\Models\CountryGroup;
use App\Services\CurrentMerchant;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

new class extends Component
{
    public $merchant;

    public bool $showForm = false;
    public ?int $editingRuleId = null;

    public string $name = '';
    public bool $isActive = true;

    public ?int $minFinishHours = null;
    public ?int $maxFinishHours = null;

    public array $selectedCategories = [];
    public array $selectedCountries = [];
    public array $selectedCountryGroups = [];
    public array $selectedLocales = [];

    public function mount(CurrentMerchant $currentMerchant): void
    {
        $this->merchant = $currentMerchant->get(auth()->user());

        if (! $this->merchant) {
            session()->flash(
                'error',
                'Select a merchant account before managing Auto Delivery.'
            );

            $this->redirectRoute('merchant.dashboard', navigate: true);

            return;
        }
    }

    public function createRule(): void
    {
        $this->resetForm();

        $this->showForm = true;
    }

    public function editRule(int $ruleId): void
    {
        $rule = $this->merchant
            ->deliveryRules()
            ->with([
                'categories:id',
                'countries:id',
                'countryGroups:id',
                'locales:id,merchant_delivery_rule_id,locale',
            ])
            ->whereKey($ruleId)
            ->firstOrFail();

        $this->editingRuleId = $rule->id;

        $this->name = $rule->name;
        $this->isActive = (bool) $rule->is_active;

        $this->minFinishHours = $rule->min_finish_hours;
        $this->maxFinishHours = $rule->max_finish_hours;

        $this->selectedCategories = $rule->categories
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();

        $this->selectedCountries = $rule->countries
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();

        $this->selectedCountryGroups = $rule->countryGroups
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();

        $this->selectedLocales = $rule->locales
            ->pluck('locale')
            ->map(fn ($locale) => (string) $locale)
            ->all();

        $this->resetValidation();

        $this->showForm = true;
    }

    public function cancelForm(): void
    {
        $this->resetForm();

        $this->showForm = false;
    }

    public function saveRule(): void
    {
        $this->normalizeSelections();

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'isActive' => ['boolean'],

            'minFinishHours' => [
                'nullable',
                'integer',
                'min:0',
                'max:87600',
            ],

            'maxFinishHours' => [
                'nullable',
                'integer',
                'min:0',
                'max:87600',
            ],

            'selectedCategories' => ['array'],
            'selectedCategories.*' => [
                'integer',
                'exists:categories,id',
            ],

            'selectedCountries' => ['array'],
            'selectedCountries.*' => [
                'integer',
                'exists:countries,id',
            ],

            'selectedCountryGroups' => ['array'],
            'selectedCountryGroups.*' => [
                'integer',
                'exists:country_groups,id',
            ],

            'selectedLocales' => ['array'],
            'selectedLocales.*' => [
                'string',
                'max:10',
            ],
        ]);

        if (
            $this->minFinishHours !== null &&
            $this->maxFinishHours !== null &&
            $this->maxFinishHours < $this->minFinishHours
        ) {
            throw ValidationException::withMessages([
                'maxFinishHours' =>
                    'Maximum finish hours must be greater than or equal to minimum finish hours.',
            ]);
        }

        /*
         * Проверяем locales не только по строке,
         * но и по реально существующим BetTranslation.
         */
        $validLocales = BetTranslation::query()
            ->whereIn('locale', $this->selectedLocales)
            ->distinct()
            ->pluck('locale')
            ->all();

        $invalidLocales = array_diff(
            $this->selectedLocales,
            $validLocales
        );

        if ($invalidLocales !== []) {
            throw ValidationException::withMessages([
                'selectedLocales' => 'One or more selected locales are invalid.',
            ]);
        }

        /*
         * Если редактируем — обязательно ищем правило
         * через текущего Merchant.
         */
        if ($this->editingRuleId) {
            $rule = $this->merchant
                ->deliveryRules()
                ->whereKey($this->editingRuleId)
                ->firstOrFail();

            $rule->update([
                'name' => trim($this->name),
                'is_active' => $this->isActive,
                'min_finish_hours' => $this->minFinishHours,
                'max_finish_hours' => $this->maxFinishHours,
            ]);
        } else {
            $rule = $this->merchant
                ->deliveryRules()
                ->create([
                    'name' => trim($this->name),
                    'is_active' => $this->isActive,
                    'min_finish_hours' => $this->minFinishHours,
                    'max_finish_hours' => $this->maxFinishHours,
                ]);
        }

        /*
         * Many-to-many criteria.
         */
        $rule->categories()->sync(
            array_map('intval', $this->selectedCategories)
        );

        $rule->countries()->sync(
            array_map('intval', $this->selectedCountries)
        );

        $rule->countryGroups()->sync(
            array_map('intval', $this->selectedCountryGroups)
        );

        /*
         * Locales — HasMany.
         */
        $rule->locales()->delete();

        if ($this->selectedLocales !== []) {
            $rule->locales()->createMany(
                collect($this->selectedLocales)
                    ->unique()
                    ->map(fn ($locale) => [
                        'locale' => $locale,
                    ])
                    ->values()
                    ->all()
            );
        }

        session()->flash(
            'auto-delivery-status',
            $this->editingRuleId
                ? 'Auto Delivery rule updated successfully.'
                : 'Auto Delivery rule created successfully.'
        );

        $this->resetForm();

        $this->showForm = false;
    }

    public function toggleRule(int $ruleId): void
    {
        $rule = $this->merchant
            ->deliveryRules()
            ->whereKey($ruleId)
            ->firstOrFail();

        $rule->update([
            'is_active' => ! $rule->is_active,
        ]);

        session()->flash(
            'auto-delivery-status',
            $rule->is_active
                ? 'Auto Delivery rule enabled.'
                : 'Auto Delivery rule disabled.'
        );
    }

    public function deleteRule(int $ruleId): void
    {
        $rule = $this->merchant
            ->deliveryRules()
            ->whereKey($ruleId)
            ->firstOrFail();

        $rule->delete();

        if ($this->editingRuleId === $ruleId) {
            $this->resetForm();
            $this->showForm = false;
        }

        session()->flash(
            'auto-delivery-status',
            'Auto Delivery rule deleted.'
        );
    }

    private function normalizeSelections(): void
    {
        $this->selectedCategories = collect($this->selectedCategories)
            ->filter(fn ($id) => $id !== '' && $id !== null)
            ->unique()
            ->values()
            ->all();

        $this->selectedCountries = collect($this->selectedCountries)
            ->filter(fn ($id) => $id !== '' && $id !== null)
            ->unique()
            ->values()
            ->all();

        $this->selectedCountryGroups = collect($this->selectedCountryGroups)
            ->filter(fn ($id) => $id !== '' && $id !== null)
            ->unique()
            ->values()
            ->all();

        $this->selectedLocales = collect($this->selectedLocales)
            ->filter(fn ($locale) => filled($locale))
            ->map(fn ($locale) => trim((string) $locale))
            ->unique()
            ->values()
            ->all();
    }

    private function resetForm(): void
    {
        $this->editingRuleId = null;

        $this->name = '';
        $this->isActive = true;

        $this->minFinishHours = null;
        $this->maxFinishHours = null;

        $this->selectedCategories = [];
        $this->selectedCountries = [];
        $this->selectedCountryGroups = [];
        $this->selectedLocales = [];

        $this->resetValidation();
    }

    public function with(): array
    {
        return [
            'rules' => $this->merchant
                ->deliveryRules()
                ->with([
                    'categories:id,name,slug',
                    'countries:id,code,name',
                    'countryGroups:id,name,slug',
                    'locales:id,merchant_delivery_rule_id,locale',
                ])
                ->latest('id')
                ->get(),

            'categories' => Category::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'slug']),

            'countries' => Country::query()
                ->orderBy('name')
                ->get(['id', 'code', 'name']),

            'countryGroups' => CountryGroup::query()
                ->orderBy('name')
                ->get(['id', 'name', 'slug']),

            'availableLocales' => BetTranslation::query()
                ->select('locale')
                ->whereNotNull('locale')
                ->where('locale', '!=', '')
                ->distinct()
                ->orderBy('locale')
                ->pluck('locale')
                ->all(),
        ];
    }
};

?>

<div class="space-y-6">

    {{-- HEADER --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-black wr-text">
                Auto Delivery
            </h1>

            <p class="mt-1 text-sm wr-muted">
                Automatically deliver newly published markets that match your rules.
            </p>
        </div>

        @if(! $showForm)
            <button
                type="button"
                wire:click="createRule"
                class="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-black text-white hover:bg-emerald-500"
            >
                + Create Rule
            </button>
        @endif
    </div>

    {{-- STATUS --}}
    @if(session('auto-delivery-status'))
        <div
            class="rounded-xl border px-4 py-3 text-sm font-bold"
            style="border-color: rgba(16,185,129,.35); background: rgba(16,185,129,.08); color: rgb(16,185,129);"
        >
            {{ session('auto-delivery-status') }}
        </div>
    @endif

    {{-- ENDPOINT WARNING --}}
    @if(! $merchant->market_url)
        <div
            class="rounded-2xl border p-5"
            style="border-color: rgba(245,158,11,.35); background: rgba(245,158,11,.07);"
        >
            <div class="font-black wr-text">
                Market URL is not configured
            </div>

            <div class="mt-1 text-sm wr-muted">
                You can configure Auto Delivery rules now, but markets cannot be delivered until a Market URL is configured in Webhooks.
            </div>

            <a
                href="{{ route('merchant.webhooks') }}"
                wire:navigate
                class="mt-3 inline-flex text-sm font-black text-amber-500 hover:underline"
            >
                Configure Webhooks →
            </a>
        </div>
    @endif

    {{-- INFO --}}
    <div class="wr-panel rounded-2xl border p-5" style="border-color: var(--wr-border);">
        <div class="font-black wr-text">
            {{ $merchant->name }}
            <span class="wr-muted">#{{ $merchant->id }}</span>
        </div>

        <div class="mt-2 text-sm wr-muted">
            Rules apply only to markets published after the rule is created.
            Existing markets can still be selected manually from Markets.
        </div>

        <div class="mt-3 text-xs wr-muted">
            Multiple values inside one criterion use OR. Different criteria use AND.
            An empty criterion means no restriction.
        </div>
    </div>

    {{-- FORM --}}
    @if($showForm)
        <div class="wr-panel rounded-2xl border p-5 sm:p-6" style="border-color: var(--wr-border);">

            <div class="mb-6 flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg font-black wr-text">
                        {{ $editingRuleId ? 'Edit Auto Delivery Rule' : 'Create Auto Delivery Rule' }}
                    </h2>

                    <p class="mt-1 text-sm wr-muted">
                        Choose which newly published markets should be delivered automatically.
                    </p>
                </div>
            </div>

            <div class="space-y-6">

                {{-- NAME --}}
                <div>
                    <label class="mb-2 block text-sm font-black wr-text">
                        Rule Name
                    </label>

                    <input
                        type="text"
                        wire:model="name"
                        placeholder="Example: Romania Crypto"
                        class="w-full rounded-xl border px-4 py-3 wr-text outline-none"
                        style="border-color: var(--wr-border); background: var(--wr-input);"
                    >

                    @error('name')
                    <div class="mt-2 text-sm font-bold text-red-500">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                {{-- ACTIVE --}}
                <label
                    class="flex cursor-pointer items-center gap-3 rounded-xl border p-4"
                    style="border-color: var(--wr-border);"
                >
                    <input
                        type="checkbox"
                        wire:model="isActive"
                        class="h-5 w-5 rounded"
                    >

                    <div>
                        <div class="text-sm font-black wr-text">
                            Active
                        </div>

                        <div class="text-xs wr-muted">
                            Matching new markets may be delivered automatically.
                        </div>
                    </div>
                </label>

                {{-- CATEGORIES --}}
                <div>
                    <div class="mb-3">
                        <div class="text-sm font-black wr-text">
                            Categories
                        </div>

                        <div class="text-xs wr-muted">
                            Leave empty to allow all categories.
                        </div>
                    </div>

                    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        @forelse($categories as $category)
                            <label
                                wire:key="category-{{ $category->id }}"
                                class="flex cursor-pointer items-center gap-3 rounded-xl border p-3"
                                style="border-color: var(--wr-border);"
                            >
                                <input
                                    type="checkbox"
                                    value="{{ $category->id }}"
                                    wire:model="selectedCategories"
                                    class="h-4 w-4 rounded"
                                >

                                <span class="text-sm font-bold wr-text">
                                    {{ $category->name }}
                                </span>
                            </label>
                        @empty
                            <div class="text-sm wr-muted">
                                No active categories.
                            </div>
                        @endforelse
                    </div>

                    @error('selectedCategories.*')
                    <div class="mt-2 text-sm font-bold text-red-500">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                {{-- COUNTRIES --}}
                <div>
                    <div class="mb-3">
                        <div class="text-sm font-black wr-text">
                            Countries
                        </div>

                        <div class="text-xs wr-muted">
                            Leave empty to allow all countries.
                        </div>
                    </div>

                    <div
                        class="grid max-h-72 gap-2 overflow-y-auto rounded-xl border p-3 sm:grid-cols-2 lg:grid-cols-3"
                        style="border-color: var(--wr-border);"
                    >
                        @foreach($countries as $country)
                            <label
                                wire:key="country-{{ $country->id }}"
                                class="flex cursor-pointer items-center gap-3 rounded-lg p-2 hover:bg-black/5"
                            >
                                <input
                                    type="checkbox"
                                    value="{{ $country->id }}"
                                    wire:model="selectedCountries"
                                    class="h-4 w-4 rounded"
                                >

                                <span class="text-sm font-bold wr-text">
                                    {{ $country->name }}
                                    <span class="wr-muted">
                                        ({{ strtoupper($country->code) }})
                                    </span>
                                </span>
                            </label>
                        @endforeach
                    </div>

                    @error('selectedCountries.*')
                    <div class="mt-2 text-sm font-bold text-red-500">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                {{-- COUNTRY GROUPS --}}
                <div>
                    <div class="mb-3">
                        <div class="text-sm font-black wr-text">
                            Country Groups
                        </div>

                        <div class="text-xs wr-muted">
                            Leave empty to allow all country groups.
                        </div>
                    </div>

                    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        @forelse($countryGroups as $group)
                            <label
                                wire:key="country-group-{{ $group->id }}"
                                class="flex cursor-pointer items-center gap-3 rounded-xl border p-3"
                                style="border-color: var(--wr-border);"
                            >
                                <input
                                    type="checkbox"
                                    value="{{ $group->id }}"
                                    wire:model="selectedCountryGroups"
                                    class="h-4 w-4 rounded"
                                >

                                <span class="text-sm font-bold wr-text">
                                    {{ $group->name }}
                                </span>
                            </label>
                        @empty
                            <div class="text-sm wr-muted">
                                No country groups.
                            </div>
                        @endforelse
                    </div>

                    @error('selectedCountryGroups.*')
                    <div class="mt-2 text-sm font-bold text-red-500">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                {{-- LOCALES --}}
                <div>
                    <div class="mb-3">
                        <div class="text-sm font-black wr-text">
                            Locales
                        </div>

                        <div class="text-xs wr-muted">
                            Leave empty to allow all available languages.
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        @forelse($availableLocales as $locale)
                            <label
                                wire:key="locale-{{ $locale }}"
                                class="flex cursor-pointer items-center gap-2 rounded-xl border px-3 py-2"
                                style="border-color: var(--wr-border);"
                            >
                                <input
                                    type="checkbox"
                                    value="{{ $locale }}"
                                    wire:model="selectedLocales"
                                    class="h-4 w-4 rounded"
                                >

                                <span class="text-sm font-black wr-text">
                                    {{ strtoupper($locale) }}
                                </span>
                            </label>
                        @empty
                            <div class="text-sm wr-muted">
                                No locales found.
                            </div>
                        @endforelse
                    </div>

                    @error('selectedLocales')
                    <div class="mt-2 text-sm font-bold text-red-500">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                {{-- FINISH WINDOW --}}
                <div>
                    <div class="mb-3">
                        <div class="text-sm font-black wr-text">
                            Market Finish Window
                        </div>

                        <div class="text-xs wr-muted">
                            Restrict markets by the number of hours remaining until finish.
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">

                        <div>
                            <label class="mb-2 block text-xs font-black wr-muted">
                                Minimum hours
                            </label>

                            <input
                                type="number"
                                min="0"
                                wire:model="minFinishHours"
                                placeholder="Example: 24"
                                class="w-full rounded-xl border px-4 py-3 wr-text outline-none"
                                style="border-color: var(--wr-border); background: var(--wr-input);"
                            >

                            @error('minFinishHours')
                            <div class="mt-2 text-sm font-bold text-red-500">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>

                        <div>
                            <label class="mb-2 block text-xs font-black wr-muted">
                                Maximum hours
                            </label>

                            <input
                                type="number"
                                min="0"
                                wire:model="maxFinishHours"
                                placeholder="Example: 720"
                                class="w-full rounded-xl border px-4 py-3 wr-text outline-none"
                                style="border-color: var(--wr-border); background: var(--wr-input);"
                            >

                            @error('maxFinishHours')
                            <div class="mt-2 text-sm font-bold text-red-500">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>

                    </div>
                </div>

                {{-- ACTIONS --}}
                <div
                    class="flex flex-col-reverse gap-3 border-t pt-5 sm:flex-row sm:justify-end"
                    style="border-color: var(--wr-border);"
                >
                    <button
                        type="button"
                        wire:click="cancelForm"
                        class="rounded-xl border px-4 py-2.5 text-sm font-black wr-text"
                        style="border-color: var(--wr-border);"
                    >
                        Cancel
                    </button>

                    <button
                        type="button"
                        wire:click="saveRule"
                        wire:loading.attr="disabled"
                        wire:target="saveRule"
                        class="rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-black text-white hover:bg-emerald-500 disabled:opacity-50"
                    >
                        <span wire:loading.remove wire:target="saveRule">
                            {{ $editingRuleId ? 'Save Changes' : 'Create Rule' }}
                        </span>

                        <span wire:loading wire:target="saveRule">
                            Saving...
                        </span>
                    </button>
                </div>

            </div>
        </div>
    @endif

    {{-- RULES --}}
    <div class="space-y-4">

        @forelse($rules as $rule)

            <div
                wire:key="delivery-rule-{{ $rule->id }}"
                class="wr-panel rounded-2xl border p-5 sm:p-6"
                style="border-color: var(--wr-border);"
            >
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">

                    <div>
                        <div class="flex flex-wrap items-center gap-3">

                            <h2 class="text-lg font-black wr-text">
                                {{ $rule->name }}
                            </h2>

                            @if($rule->is_active)
                                <span class="rounded-full bg-emerald-500/10 px-2.5 py-1 text-xs font-black text-emerald-500">
                                    ACTIVE
                                </span>
                            @else
                                <span class="rounded-full bg-slate-500/10 px-2.5 py-1 text-xs font-black wr-muted">
                                    DISABLED
                                </span>
                            @endif

                        </div>

                        <div class="mt-1 text-xs wr-muted">
                            Rule #{{ $rule->id }}
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2">

                        <button
                            type="button"
                            wire:click="toggleRule({{ $rule->id }})"
                            class="rounded-lg border px-3 py-2 text-xs font-black wr-text"
                            style="border-color: var(--wr-border);"
                        >
                            {{ $rule->is_active ? 'Disable' : 'Enable' }}
                        </button>

                        <button
                            type="button"
                            wire:click="editRule({{ $rule->id }})"
                            class="rounded-lg border px-3 py-2 text-xs font-black wr-text"
                            style="border-color: var(--wr-border);"
                        >
                            Edit
                        </button>

                        <button
                            type="button"
                            wire:click="deleteRule({{ $rule->id }})"
                            wire:confirm="Delete this Auto Delivery rule?"
                            class="rounded-lg border border-red-500/30 px-3 py-2 text-xs font-black text-red-500"
                        >
                            Delete
                        </button>

                    </div>
                </div>

                <div class="mt-6 grid gap-5 md:grid-cols-2 xl:grid-cols-3">

                    <div>
                        <div class="text-xs font-black uppercase tracking-wide wr-muted">
                            Categories
                        </div>

                        <div class="mt-2 text-sm font-bold wr-text">
                            {{ $rule->categories->isNotEmpty()
                                ? $rule->categories->pluck('name')->join(', ')
                                : 'Any category' }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-black uppercase tracking-wide wr-muted">
                            Countries
                        </div>

                        <div class="mt-2 text-sm font-bold wr-text">
                            {{ $rule->countries->isNotEmpty()
                                ? $rule->countries->pluck('name')->join(', ')
                                : 'Any country' }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-black uppercase tracking-wide wr-muted">
                            Country Groups
                        </div>

                        <div class="mt-2 text-sm font-bold wr-text">
                            {{ $rule->countryGroups->isNotEmpty()
                                ? $rule->countryGroups->pluck('name')->join(', ')
                                : 'Any group' }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-black uppercase tracking-wide wr-muted">
                            Locales
                        </div>

                        <div class="mt-2 text-sm font-bold wr-text">
                            {{ $rule->locales->isNotEmpty()
                                ? $rule->locales->pluck('locale')->map(fn ($locale) => strtoupper($locale))->join(', ')
                                : 'Any locale' }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-black uppercase tracking-wide wr-muted">
                            Minimum Finish
                        </div>

                        <div class="mt-2 text-sm font-bold wr-text">
                            {{ $rule->min_finish_hours !== null
                                ? $rule->min_finish_hours . ' hours'
                                : 'No minimum' }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-black uppercase tracking-wide wr-muted">
                            Maximum Finish
                        </div>

                        <div class="mt-2 text-sm font-bold wr-text">
                            {{ $rule->max_finish_hours !== null
                                ? $rule->max_finish_hours . ' hours'
                                : 'No maximum' }}
                        </div>
                    </div>

                </div>

            </div>

        @empty

            <div
                class="wr-panel rounded-2xl border p-10 text-center"
                style="border-color: var(--wr-border);"
            >
                <div class="text-lg font-black wr-text">
                    No Auto Delivery rules yet
                </div>

                <div class="mx-auto mt-2 max-w-xl text-sm wr-muted">
                    Create a rule to automatically deliver newly published markets matching your criteria.
                </div>

                <button
                    type="button"
                    wire:click="createRule"
                    class="mt-5 rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-black text-white hover:bg-emerald-500"
                >
                    + Create First Rule
                </button>
            </div>

        @endforelse

    </div>

</div>
