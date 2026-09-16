@props(['technology'])
@php $logo = $technology->relationLoaded('media') ? $technology->getFirstMedia('logo') : null; @endphp
<div {{ $attributes->merge(['class' => 'flex items-center gap-3 rounded-card border border-line bg-surface px-4 py-3']) }}>
    @if ($logo)
        <img src="{{ $logo->hasGeneratedConversion('thumb') ? $logo->getUrl('thumb') : $logo->getUrl() }}" alt="{{ $logo->getCustomProperty('alt', $technology->name) }}" class="size-8 object-contain" width="32" height="32" loading="lazy" decoding="async">
    @else
        <span class="flex size-8 items-center justify-center rounded-control bg-canvas-muted text-meta text-fg-secondary" aria-hidden="true">{{ mb_substr($technology->name, 0, 2) }}</span>
    @endif
    <span class="text-body-sm font-medium text-fg">{{ $technology->name }}</span>
</div>
