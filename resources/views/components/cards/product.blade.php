@props(['product', 'layout' => 'grid'])
@inject('urls', 'App\Services\Cms\PublicUrl')
@php $logo = $product->relationLoaded('media') ? $product->getFirstMedia('logo') : null; $hero = $product->relationLoaded('media') ? $product->getFirstMedia('hero') : null; @endphp
<x-ui.card :href="$urls->pathFor($product)" {{ $attributes }}>
    <div class="flex items-center justify-between gap-3">
        @if ($logo)
            <img src="{{ $logo->hasGeneratedConversion('thumb') ? $logo->getUrl('thumb') : $logo->getUrl() }}" alt="{{ $logo->getCustomProperty('alt', $product->name.' logo') }}" class="h-8 w-auto" loading="lazy" decoding="async">
        @else
            <x-ui.eyebrow>Product</x-ui.eyebrow>
        @endif
        @if ($product->isComingSoon())
            <x-ui.badge tone="info">Coming soon</x-ui.badge>
        @endif
    </div>
    <h3 class="mt-4 text-h3">{{ $product->name }}</h3>
    @if ($product->tagline)
        <p class="mt-1 text-body font-medium text-brand">{{ $product->tagline }}</p>
    @endif
    @if ($product->short_description)
        <p class="mt-3 text-body-sm text-fg-secondary">{{ \Illuminate\Support\Str::limit($product->short_description, 160) }}</p>
    @endif
    @if ($hero)
        <x-ui.picture :media="$hero" conversion="card" class="mt-5" sizes="(min-width: 1024px) 33vw, 100vw" />
    @endif
    <span class="mt-4 inline-flex items-center gap-1.5 text-button text-fg group-hover:text-brand">View product <x-ui.icon name="heroicon-m-arrow-right" class="size-4 transition-transform group-hover:translate-x-0.5" /></span>
</x-ui.card>
