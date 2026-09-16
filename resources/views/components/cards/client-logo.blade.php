@props(['client'])
@php $logo = $client->relationLoaded('media') ? $client->getFirstMedia('logo') : null; @endphp
<div {{ $attributes->merge(['class' => 'flex h-16 items-center justify-center rounded-card border border-line bg-surface px-5']) }}>
    @if ($logo)
        <img src="{{ $logo->hasGeneratedConversion('thumb') ? $logo->getUrl('thumb') : $logo->getUrl() }}" alt="{{ $logo->getCustomProperty('alt', $client->name) }}" class="max-h-8 w-auto object-contain" loading="lazy" decoding="async">
    @else
        <span class="text-body-sm font-semibold text-fg-secondary">{{ $client->name }}</span>
    @endif
</div>
