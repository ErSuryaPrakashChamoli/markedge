{{-- Google map of the office from the Contact settings (map embed code, map location, or the address). --}}
@inject('settings', 'App\Services\Cms\Settings')
@php
    $embedUrl = $settings->mapEmbedUrl();
    $directionsUrl = $settings->mapDirectionsUrl();
@endphp
@if ($embedUrl)
    <figure {{ $attributes }}>
        <div class="relative aspect-4/3 overflow-hidden rounded-card border border-line bg-surface">
            <iframe src="{{ $embedUrl }}" title="Map showing the {{ $settings->get('company.name', config('app.name')) }} office" class="absolute inset-0 size-full border-0" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
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
