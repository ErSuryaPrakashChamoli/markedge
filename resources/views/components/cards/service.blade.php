@props(['service', 'showCategory' => false])
@inject('urls', 'App\Services\Cms\PublicUrl')
<x-ui.card :href="$urls->pathFor($service)" {{ $attributes }}>
    @if ($showCategory && $service->relationLoaded('category') && $service->category)
        <x-ui.badge class="self-start">{{ $service->category->name }}</x-ui.badge>
    @endif
    <span class="mt-3 inline-flex size-11 items-center justify-center rounded-control bg-brand-soft text-brand transition-colors group-hover:bg-brand group-hover:text-white first:mt-0"><x-ui.icon name="heroicon-o-squares-2x2" class="size-5" /></span>
    <h3 class="mt-4 text-h4">{{ $service->name }}</h3>
    @if ($service->short_description ?? $service->tagline)
        <p class="mt-2 text-body-sm text-fg-secondary">{{ \Illuminate\Support\Str::limit($service->short_description ?: $service->tagline, 140) }}</p>
    @endif
    <span class="mt-4 inline-flex items-center gap-1.5 text-button text-fg group-hover:text-brand">Explore <x-ui.icon name="heroicon-m-arrow-right" class="size-4 transition-transform group-hover:translate-x-0.5" /></span>
</x-ui.card>
