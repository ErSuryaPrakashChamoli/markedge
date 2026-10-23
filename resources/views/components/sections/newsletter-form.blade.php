{{--
    Newsletter sign-up body: copy on the left, form on the right. A plain POST to /newsletter, so it works on
    pages that ship no Livewire. Errors and the thank-you only show on the form that was submitted (placement).
    Every text falls back to Global Settings → Newsletter.
--}}
@props(['heading' => null, 'intro' => null, 'buttonLabel' => null, 'placement' => 'block'])
@inject('settings', 'App\Services\Cms\Settings')
@php
    $heading = filled($heading) ? $heading : $settings->get('newsletter.heading', 'Ideas for growing businesses, in your inbox.');
    $intro = filled($intro) ? $intro : $settings->get('newsletter.intro', 'A short note on technology, IT infrastructure and digital growth, with new blogs, news and videos from Markedge. No spam.');
    $buttonLabel = filled($buttonLabel) ? $buttonLabel : $settings->get('newsletter.button_label', 'Subscribe');
    $consentText = $settings->get('newsletter.consent_text', \App\Http\Controllers\Site\NewsletterController::DEFAULT_CONSENT_TEXT);
    $successMessage = $settings->get('newsletter.success_message', 'Thanks for subscribing. Look out for the next issue in your inbox.');
    $anchor = 'newsletter-'.$placement;
    $submittedHere = old('placement') === $placement;
    $bag = $errors->getBag('newsletter');
    $emailError = $submittedHere ? $bag->first('email') : null;
    $consentError = $submittedHere ? $bag->first('consent') : null;
    $subscribed = session('newsletter.subscribed') === $placement;
@endphp
<div {{ $attributes->merge(['class' => 'grid gap-8 lg:grid-cols-[1fr_1.1fr] lg:items-center lg:gap-16']) }}>
    <div class="max-w-xl">
        <x-ui.eyebrow>Newsletter</x-ui.eyebrow>
        <h2 class="mt-4 text-h3 text-fg">{{ $heading }}</h2>
        @if (filled($intro))
            <p class="mt-3 text-body text-fg-secondary">{{ $intro }}</p>
        @endif
    </div>

    <div>
        @if ($subscribed)
            <div role="status" aria-live="polite" class="flex items-start gap-3 rounded-card border border-line bg-surface/60 p-5">
                <x-ui.icon name="heroicon-o-check-circle" class="size-7 shrink-0 text-success" />
                <p class="pt-0.5 text-body text-fg">{{ $successMessage }}</p>
            </div>
        @else
            <form method="POST" action="{{ route('newsletter.subscribe') }}" novalidate>
                @csrf
                <input type="hidden" name="placement" value="{{ $placement }}">
                <input type="hidden" name="source" value="/{{ ltrim(request()->path(), '/') }}">
                <div class="hidden" aria-hidden="true">
                    <label for="{{ $anchor }}-website">Website</label>
                    <input id="{{ $anchor }}-website" type="text" name="website" tabindex="-1" autocomplete="off">
                </div>

                <div class="flex flex-col gap-3 sm:flex-row">
                    <label for="{{ $anchor }}-email" class="sr-only">Email address</label>
                    <x-forms.input :id="$anchor.'-email'" type="email" name="email" :value="$submittedHere ? old('email') : null" placeholder="you@company.com" autocomplete="email" maxlength="255" required :invalid="filled($emailError)" class="h-12 sm:flex-1" />
                    <x-ui.button type="submit" size="lg" icon="heroicon-m-arrow-right" class="shrink-0">{{ $buttonLabel }}</x-ui.button>
                </div>
                @if ($emailError)
                    <p id="{{ $anchor }}-email-error" class="mt-2 text-caption text-danger" role="alert">{{ $emailError }}</p>
                @endif

                @if (filled($consentText))
                    <div class="mt-4">
                        <x-forms.checkbox :id="$anchor.'-consent'" name="consent" value="1" :label="$consentText" :checked="$submittedHere && old('consent')" />
                        @if ($consentError)
                            <p class="mt-1.5 text-caption text-danger" role="alert">{{ $consentError }}</p>
                        @endif
                    </div>
                @endif
            </form>
        @endif
    </div>
</div>
