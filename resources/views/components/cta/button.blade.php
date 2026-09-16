{{-- Renders a Cta model's primary (or secondary) action as a button. --}}
@inject('ctas', 'App\Services\Cms\CtaResolver')
@props(['cta', 'entity' => null, 'secondary' => false, 'variant' => null, 'size' => 'md'])
@php
    $raw = $secondary ? $ctas->secondaryHref($cta, $entity) : $ctas->primaryHref($cta, $entity);
    $href = $ctas->trackedHref($cta, $secondary ? 'secondary' : 'primary', $entity) ?? $raw;
    $label = $secondary ? $cta->secondary_label : $cta->primary_label;
    $variant ??= $secondary ? 'outline' : 'primary';
    $external = $ctas->isExternal($raw) && ! str_starts_with((string) $raw, url('/'));
@endphp
@if ($href && $label)
    <x-ui.button :href="$href" :variant="$variant" :size="$size" :target="$external ? '_blank' : null" :rel="trim(($external ? 'noopener ' : '').'nofollow')" {{ $attributes }}>
        {{ $label }}
    </x-ui.button>
@endif
