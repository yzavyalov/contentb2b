<div class="rounded-3xl border border-[var(--wr-border)] bg-[var(--wr-panel)] p-6 shadow-sm sm:p-8">

    <div class="flex items-start justify-between gap-6">

        <div>
            <div class="text-xs font-black uppercase tracking-[0.18em] text-lime-500">
                Application
            </div>

            <h1 class="mt-2 text-3xl font-black text-[var(--wr-text)]">
                @if ($selectedType === \App\Enums\AccountApplicationType::MERCHANT->value)
                    Create your Merchant
                @else
                    Become a Content Creator
                @endif
            </h1>
        </div>

        @if (! $application)
            <button
                type="button"
                wire:click="back"
                class="rounded-xl border border-[var(--wr-border)] px-4 py-2 text-sm font-bold text-[var(--wr-text)]"
            >
                ← Back
            </button>
        @endif

    </div>

    <div class="mt-8 space-y-6">

        @if ($selectedType === \App\Enums\AccountApplicationType::CONTENT_MANAGER->value)

            <div>
                <label class="mb-2 block text-sm font-bold text-[var(--wr-text)]">
                    Display name / nickname
                </label>

                <input
                    type="text"
                    wire:model="displayName"
                    class="w-full rounded-xl border border-[var(--wr-border)] bg-[var(--wr-input)] px-4 py-3 text-[var(--wr-text)]"
                    placeholder="Your public name"
                >

                @error('displayName')
                <div class="mt-2 text-sm font-semibold text-red-500">
                    {{ $message }}
                </div>
                @enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-bold text-[var(--wr-text)]">
                    Preferred language
                </label>

                <select
                    wire:model="preferredLocale"
                    class="w-full rounded-xl border border-[var(--wr-border)] bg-[var(--wr-input)] px-4 py-3 text-[var(--wr-text)]"
                >
                    <option value="en">English</option>
                    <option value="ru">Russian</option>
                    <option value="ro">Romanian</option>
                    <option value="uk">Ukrainian</option>
                    <option value="de">German</option>
                    <option value="fr">French</option>
                    <option value="es">Spanish</option>
                </select>

                @error('preferredLocale')
                <div class="mt-2 text-sm font-semibold text-red-500">
                    {{ $message }}
                </div>
                @enderror
            </div>

        @else

            <div>
                <label class="mb-2 block text-sm font-bold text-[var(--wr-text)]">
                    Merchant / company name
                </label>

                <input
                    type="text"
                    wire:model="companyName"
                    class="w-full rounded-xl border border-[var(--wr-border)] bg-[var(--wr-input)] px-4 py-3 text-[var(--wr-text)]"
                    placeholder="Company or project name"
                >

                @error('companyName')
                <div class="mt-2 text-sm font-semibold text-red-500">
                    {{ $message }}
                </div>
                @enderror
            </div>

        @endif

        <div>
            <label class="mb-2 block text-sm font-bold text-[var(--wr-text)]">
                Contact email
            </label>

            <input
                type="email"
                wire:model="contactEmail"
                class="w-full rounded-xl border border-[var(--wr-border)] bg-[var(--wr-input)] px-4 py-3 text-[var(--wr-text)]"
            >

            @error('contactEmail')
            <div class="mt-2 text-sm font-semibold text-red-500">
                {{ $message }}
            </div>
            @enderror
        </div>

        <div class="grid gap-5 md:grid-cols-2">

            <div>
                <label class="mb-2 block text-sm font-bold text-[var(--wr-text)]">
                    Telegram
                </label>

                <input
                    type="text"
                    wire:model="telegram"
                    class="w-full rounded-xl border border-[var(--wr-border)] bg-[var(--wr-input)] px-4 py-3 text-[var(--wr-text)]"
                    placeholder="@username"
                >
            </div>

            <div>
                <label class="mb-2 block text-sm font-bold text-[var(--wr-text)]">
                    Phone
                </label>

                <input
                    type="text"
                    wire:model="phone"
                    class="w-full rounded-xl border border-[var(--wr-border)] bg-[var(--wr-input)] px-4 py-3 text-[var(--wr-text)]"
                    placeholder="+40..."
                >
            </div>

        </div>

        @if ($selectedType === \App\Enums\AccountApplicationType::MERCHANT->value)

            <div>
                <label class="mb-2 block text-sm font-bold text-[var(--wr-text)]">
                    Website
                </label>

                <input
                    type="url"
                    wire:model="website"
                    class="w-full rounded-xl border border-[var(--wr-border)] bg-[var(--wr-input)] px-4 py-3 text-[var(--wr-text)]"
                    placeholder="https://example.com"
                >

                @error('website')
                <div class="mt-2 text-sm font-semibold text-red-500">
                    {{ $message }}
                </div>
                @enderror
            </div>

        @endif

        <div>
            <label class="mb-2 block text-sm font-bold text-[var(--wr-text)]">
                Additional information
            </label>

            <textarea
                wire:model="message"
                rows="5"
                class="w-full rounded-xl border border-[var(--wr-border)] bg-[var(--wr-input)] px-4 py-3 text-[var(--wr-text)]"
                placeholder="Tell us a little about yourself or your project..."
            ></textarea>

            @error('message')
            <div class="mt-2 text-sm font-semibold text-red-500">
                {{ $message }}
            </div>
            @enderror
        </div>

        <div class="border-t border-[var(--wr-border)] pt-6">

            <button
                type="button"
                wire:click="submit"
                wire:loading.attr="disabled"
                wire:target="submit"
                class="inline-flex items-center justify-center rounded-xl bg-lime-400 px-7 py-3 font-black text-[#07111f] transition hover:bg-lime-300 disabled:opacity-50"
            >
                <span wire:loading.remove wire:target="submit">
                    Submit Application →
                </span>

                <span wire:loading wire:target="submit">
                    Submitting...
                </span>
            </button>

        </div>

    </div>

</div>
