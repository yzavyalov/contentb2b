<?php

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

new class extends Component
{
    public string $name = '';
    public string $company = '';
    public string $email = '';
    public string $website = '';
    public string $contact = '';
    public string $interests = '';
    public string $message = '';

    /*
     * Honeypot. Real users never see or fill this field.
     */
    public string $company_website = '';

    public bool $sent = false;

    public function submit(): void
    {
        /*
         * Silently reject obvious bots.
         */
        if ($this->company_website !== '') {
            $this->resetForm();

            $this->sent = true;

            return;
        }

        $data = $this->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'company' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
            ],

            'website' => [
                'nullable',
                'string',
                'max:500',
            ],

            'contact' => [
                'nullable',
                'string',
                'max:255',
            ],

            'interests' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'message' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ]);

        /*
         * Limit repeated submissions from the same session/IP.
         */
        $key = 'request-demo:' . request()->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Too many requests. Please try again later.',
            ]);
        }

        RateLimiter::hit($key, 3600);

        $recipient = config('mail.contact_to');

        Mail::raw(
            $this->buildEmailBody($data),
            function ($mail) use ($recipient, $data) {
                $mail
                    ->to($recipient)
                    ->replyTo(
                        $data['email'],
                        $data['name']
                    )
                    ->subject(
                        'wrangle.win — Demo Request — ' . $data['company']
                    );
            }
        );

        $this->resetForm();

        $this->sent = true;
    }

    private function buildEmailBody(array $data): string
    {
        return implode(PHP_EOL, [
            'New demo request from wrangle.win',
            '',
            'Name: ' . $data['name'],
            'Company: ' . $data['company'],
            'Email: ' . $data['email'],
            'Website: ' . ($data['website'] ?: '—'),
            'Telegram / Phone: ' . ($data['contact'] ?: '—'),
            '',
            'Content interests:',
            $data['interests'] ?: '—',
            '',
            'Message:',
            $data['message'] ?: '—',
            '',
            'Submitted: ' . now()->format('Y-m-d H:i:s'),
        ]);
    }

    private function resetForm(): void
    {
        $this->reset([
            'name',
            'company',
            'email',
            'website',
            'contact',
            'interests',
            'message',
            'company_website',
        ]);
    }
};

?>

<section
    id="request-demo"
    class="bg-white py-16 sm:py-20"
>
    <div class="mx-auto max-w-7xl px-5 sm:px-8">

        <div class="grid overflow-hidden rounded-[2rem] bg-[#07111f] shadow-2xl lg:grid-cols-[0.85fr_1.15fr]">

            {{-- COPY --}}
            <div class="relative overflow-hidden p-7 text-white sm:p-10 lg:p-12">

                <div class="pointer-events-none absolute -left-24 top-10 h-72 w-72 rounded-full bg-lime-400/10 blur-3xl"></div>
                <div class="pointer-events-none absolute bottom-0 right-0 h-72 w-72 rounded-full bg-cyan-500/10 blur-3xl"></div>

                <div class="relative">

                    <div class="text-xs font-black uppercase tracking-[0.18em] text-lime-300">
                        Request Demo
                    </div>

                    <h2 class="mt-4 max-w-lg text-3xl font-black tracking-tight sm:text-4xl">
                        See how wrangle.win can fit into your product.
                    </h2>

                    <p class="mt-5 max-w-lg text-base leading-7 text-slate-300">
                        Tell us about your platform and the prediction market content you need.
                        We'll show you relevant content, delivery options and how the integration works.
                    </p>

                    <div class="mt-8 space-y-4">

                        <div class="flex gap-3">
                            <span class="text-lime-300">✓</span>
                            <span class="text-sm font-semibold text-slate-200">
                                Content tailored to your categories
                            </span>
                        </div>

                        <div class="flex gap-3">
                            <span class="text-lime-300">✓</span>
                            <span class="text-sm font-semibold text-slate-200">
                                Multilingual market delivery
                            </span>
                        </div>

                        <div class="flex gap-3">
                            <span class="text-lime-300">✓</span>
                            <span class="text-sm font-semibold text-slate-200">
                                Monitoring and verified resolution
                            </span>
                        </div>

                        <div class="flex gap-3">
                            <span class="text-lime-300">✓</span>
                            <span class="text-sm font-semibold text-slate-200">
                                API integration overview
                            </span>
                        </div>

                    </div>

                </div>
            </div>

            {{-- FORM --}}
            <div class="bg-slate-50 p-7 sm:p-10 lg:p-12">

                @if($sent)

                    <div class="flex min-h-[480px] items-center justify-center">

                        <div class="max-w-md text-center">

                            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-lime-100 text-2xl font-black text-lime-600">
                                ✓
                            </div>

                            <h3 class="mt-6 text-2xl font-black text-slate-950">
                                Thank you.
                            </h3>

                            <p class="mt-3 leading-7 text-slate-600">
                                Your request has been received.
                                We'll get back to you shortly.
                            </p>

                            <button
                                type="button"
                                wire:click="$set('sent', false)"
                                class="mt-7 text-sm font-black text-emerald-600 transition hover:text-emerald-500"
                            >
                                Send another request
                            </button>

                        </div>

                    </div>

                @else

                    <form
                        wire:submit="submit"
                        class="space-y-5"
                    >

                        {{-- Honeypot --}}
                        <div
                            aria-hidden="true"
                            class="absolute -left-[10000px] top-auto h-px w-px overflow-hidden"
                        >
                            <label>
                                Company Website
                                <input
                                    type="text"
                                    wire:model="company_website"
                                    tabindex="-1"
                                    autocomplete="off"
                                >
                            </label>
                        </div>

                        <div class="grid gap-5 sm:grid-cols-2">

                            <div>
                                <label class="text-sm font-black text-slate-800">
                                    Name *
                                </label>

                                <input
                                    type="text"
                                    wire:model="name"
                                    autocomplete="name"
                                    class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-slate-950 outline-none transition focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10"
                                >

                                @error('name')
                                <div class="mt-2 text-xs font-bold text-red-500">
                                    {{ $message }}
                                </div>
                                @enderror
                            </div>

                            <div>
                                <label class="text-sm font-black text-slate-800">
                                    Company *
                                </label>

                                <input
                                    type="text"
                                    wire:model="company"
                                    autocomplete="organization"
                                    class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-slate-950 outline-none transition focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10"
                                >

                                @error('company')
                                <div class="mt-2 text-xs font-bold text-red-500">
                                    {{ $message }}
                                </div>
                                @enderror
                            </div>

                        </div>

                        <div class="grid gap-5 sm:grid-cols-2">

                            <div>
                                <label class="text-sm font-black text-slate-800">
                                    Work Email *
                                </label>

                                <input
                                    type="email"
                                    wire:model="email"
                                    autocomplete="email"
                                    class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-slate-950 outline-none transition focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10"
                                >

                                @error('email')
                                <div class="mt-2 text-xs font-bold text-red-500">
                                    {{ $message }}
                                </div>
                                @enderror
                            </div>

                            <div>
                                <label class="text-sm font-black text-slate-800">
                                    Website
                                </label>

                                <input
                                    type="text"
                                    wire:model="website"
                                    placeholder="https://"
                                    class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-slate-950 outline-none transition focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10"
                                >

                                @error('website')
                                <div class="mt-2 text-xs font-bold text-red-500">
                                    {{ $message }}
                                </div>
                                @enderror
                            </div>

                        </div>

                        <div>
                            <label class="text-sm font-black text-slate-800">
                                Telegram / Phone
                            </label>

                            <input
                                type="text"
                                wire:model="contact"
                                class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-slate-950 outline-none transition focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10"
                            >

                            @error('contact')
                            <div class="mt-2 text-xs font-bold text-red-500">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>

                        <div>
                            <label class="text-sm font-black text-slate-800">
                                What content are you interested in?
                            </label>

                            <input
                                type="text"
                                wire:model="interests"
                                placeholder="Sports, crypto, politics, finance, esports..."
                                class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-slate-950 outline-none transition focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10"
                            >

                            @error('interests')
                            <div class="mt-2 text-xs font-bold text-red-500">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>

                        <div>
                            <label class="text-sm font-black text-slate-800">
                                Message
                            </label>

                            <textarea
                                wire:model="message"
                                rows="4"
                                placeholder="Tell us about your platform, markets, languages or integration requirements."
                                class="mt-2 w-full resize-y rounded-xl border border-slate-200 bg-white px-4 py-3 text-slate-950 outline-none transition focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10"
                            ></textarea>

                            @error('message')
                            <div class="mt-2 text-xs font-bold text-red-500">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>

                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            wire:target="submit"
                            class="inline-flex w-full items-center justify-center rounded-xl bg-lime-400 px-6 py-4 text-sm font-black text-slate-950 transition hover:bg-lime-300 disabled:cursor-wait disabled:opacity-60"
                        >
                            <span wire:loading.remove wire:target="submit">
                                Request Demo
                            </span>

                            <span wire:loading wire:target="submit">
                                Sending...
                            </span>
                        </button>

                        <p class="text-center text-xs leading-5 text-slate-500">
                            We'll use your details only to respond to your request and discuss wrangle.win services.
                        </p>

                    </form>

                @endif

            </div>

        </div>

    </div>
</section>
