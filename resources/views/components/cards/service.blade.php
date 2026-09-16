@props(['service', 'showCategory' => false])
@inject('urls', 'App\Services\Cms\PublicUrl')
<x-ui.card :href="$urls->pathFor($service)" {{ $attributes }}>
    @if ($showCategory && $service->relationLoaded('category') && $service->category)
        <x-ui.badge class="self-start">{{ $service->category->name }}</x-ui.badge>
    @endif
    <h3 class="text-h4 mt-3 first:mt-0">{{ $service->name }}</h3>
    @if ($service->short_description ?? $service->tagline)
        <p class="mt-2 text-body-sm text-fg-secondary">{{ \Illuminate\Support\Str::limit($service->short_description ?: $service->tagline, 140) }}</p>
    @endif
    <span class="mt-4 inline-flex items-center gap-1.5 text-button text-fg group-hover:text-brand">Explore <x-ui.icon name="heroicon-m-arrow-right" class="size-4 transition-transform group-hover:translate-x-0.5" /></span>
</x-ui.card>
