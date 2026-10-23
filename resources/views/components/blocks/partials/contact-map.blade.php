{{--
    Google map of the office from the Contact settings (map embed code, map location, or the address).
    frame: size classes for the map box (4:3 on the Contact page, a short strip in the footer).
    captionAbove: put the heading and "Get directions" over the map, so the map itself is the bottom edge.
--}}
@props(['frame' => 'aspect-4/3', 'captionAbove' => false, 'heading' => null])
@inject('settings', 'App\Services\Cms\Settings')
@php
    $embedUrl = $settings->mapEmbedUrl();
    $directionsUrl = $settings->mapDirectionsUrl();
@endphp
@if ($embedUrl)
    <figure {{ $attributes }} data-office-map>
        @if ($captionAbove && ($heading || $directionsUrl))
            <figcaption class="mb-4 flex items-center justify-between gap-4">
                @if ($heading)
                    <span class="text-eyebrow text-fg">{{ $heading }}</span>
                @endif
                @if ($directionsUrl)
                    <a href="{{ $directionsUrl }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 text-body-sm font-medium text-brand transition-colors hover:text-brand-hover">
                        Get directions
                        <x-ui.icon name="heroicon-m-arrow-top-right-on-square" class="size-4" />
                    </a>
                @endif
            </figcaption>
        @endif
        <div class="group/map relative {{ $frame }} overflow-hidden rounded-card border border-line bg-surface shadow-card">
            <iframe src="{{ $embedUrl }}" title="Map showing the {{ $settings->get('company.name', config('app.name')) }} office" class="absolute inset-0 size-full border-0 grayscale-40 transition duration-500 group-hover/map:grayscale-0" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
        </div>
        @if ($directionsUrl && ! $captionAbove)
            <figcaption class="mt-3">
                <a href="{{ $directionsUrl }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 text-body-sm font-medium text-brand transition-colors hover:text-brand-hover">
                    Get directions
                    <x-ui.icon name="heroicon-m-arrow-top-right-on-square" class="size-4" />
                </a>
            </figcaption>
        @endif
    </figure>
@endif
