{{--
    Google map of the office from the Contact settings (map embed code, map location, or the address).
    wide: full-width banner (footer) instead of the 4:3 card beside the contact form.
--}}
@props(['wide' => false])
@inject('settings', 'App\Services\Cms\Settings')
@php
    $embedUrl = $settings->mapEmbedUrl();
    $directionsUrl = $settings->mapDirectionsUrl();
@endphp
@if ($embedUrl)
    <figure {{ $attributes }}>
        <div @class([
            'group/map relative overflow-hidden rounded-card border border-line bg-surface shadow-card',
            'aspect-4/3' => ! $wide,
            'h-72 sm:h-80 lg:h-[28rem]' => $wide,
        ])>
            <iframe src="{{ $embedUrl }}" title="Map showing the {{ $settings->get('company.name', config('app.name')) }} office" class="absolute inset-0 size-full border-0 grayscale-40 transition duration-500 group-hover/map:grayscale-0" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
        </div>
        @if ($directionsUrl)
            <figcaption class="mt-3">
                <a href="{{ $directionsUrl }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 text-body-sm font-medium text-brand transition-colors hover:text-brand-hover">
                    Get directions
                    <x-ui.icon name="heroicon-m-arrow-top-right-on-square" class="size-4" />
                </a>
            </figcaption>
        @endif
    </figure>
@endif
