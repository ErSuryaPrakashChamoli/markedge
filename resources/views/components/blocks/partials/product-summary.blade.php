@props(['product', 'logo' => null])
@inject('urls', 'App\Services\Cms\PublicUrl')
<div>
    <div class="flex items-center gap-3">
        @if ($logo)
            <img src="{{ $logo->hasGeneratedConversion('thumb') ? $logo->getUrl('thumb') : $logo->getUrl() }}" alt="{{ $logo->getCustomProperty('alt', $product->name.' logo') }}" class="h-9 w-auto" loading="lazy" decoding="async">
        @endif
        @if ($product->isComingSoon())
            <x-ui.badge tone="info">Coming soon</x-ui.badge>
        @endif
    </div>
    <h3 class="mt-4 text-h2">{{ $product->name }}</h3>
    @if ($product->tagline)
        <p class="mt-2 text-body-lg font-medium text-brand">{{ $product->tagline }}</p>
    @endif
    @if ($product->short_description)
        <p class="mt-4 text-body-lg text-fg-secondary">{{ $product->short_description }}</p>
    @endif
    @if (filled($product->benefits))
        <ul class="mt-6 grid gap-2 sm:grid-cols-2">
            @foreach (collect($product->benefits)->take(4) as $benefit)
                @if (filled($benefit['title'] ?? null))<li class="flex items-start gap-2 text-body-sm text-fg"><x-ui.icon name="heroicon-m-check" class="mt-0.5 size-4 text-brand" />{{ $benefit['title'] }}</li>@endif
            @endforeach
        </ul>
    @endif
    <div class="mt-8"><x-ui.button :href="$urls->pathFor($product)" icon="heroicon-m-arrow-right">Explore {{ $product->name }}</x-ui.button></div>
</div>
