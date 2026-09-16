{{-- Renders a Cta model's primary (or secondary) action as a button. --}}
@inject('ctas', 'App\Services\Cms\CtaResolver')
@props(['cta', 'entity' => null, 'secondary' => false, 'variant' => null, 'size' => 'md'])
@php
    $href = $secondary ? $ctas->secondaryHref($cta, $entity) : $ctas->primaryHref($cta, $entity);
    $label = $secondary ? $cta->secondary_label : $cta->primary_label;
    $variant ??= $secondary ? 'outline' : 'primary';
    $external = $ctas->isExternal($href) && ! str_starts_with((string) $href, url('/'));
@endphp
@if ($href && $label)
    <x-ui.button :href="$href" :variant="$variant" :size="$size" :target="$external ? '_blank' : null" :rel="$external ? 'noopener' : null" {{ $attributes }}>
        {{ $label }}
    </x-ui.button>
@endif
