@props(['data', 'host' => null, 'preview' => false])
@inject('urls', 'App\Services\Cms\PublicUrl')
@php $category = $data['category'] ?? null; $heading = filled($data['heading'] ?? null) ? $data['heading'] : ($category?->name ?? 'Services'); @endphp
<x-ui.section :theme="$data['theme'] ?? 'light'" :id="$data['anchor'] ?? null">
    <div class="mb-10 flex flex-wrap items-end justify-between gap-4">
        <x-ui.section-header :eyebrow="$category?->pillar_label" :title="$heading" :intro="filled($data['intro'] ?? null) ? $data['intro'] : $category?->tagline" />
        @if ($category && ($data['show_category_cta'] ?? true))
            <x-ui.arrow-link :href="$urls->pathFor($category)">{{ filled($data['cta_label'] ?? null) ? $data['cta_label'] : 'Explore '.$category->name }}</x-ui.arrow-link>
        @endif
    </div>
    @if (($data['layout'] ?? 'grid') === 'list')
        <ul class="divide-y divide-line border-y border-line">
            @foreach ($data['services'] as $service)
                <li><a href="{{ $urls->pathFor($service) }}" class="group flex items-center justify-between gap-6 py-5"><span><span class="block text-h4 text-fg group-hover:text-brand">{{ $service->name }}</span>@if ($service->tagline)<span class="mt-1 block text-body-sm text-fg-secondary">{{ $service->tagline }}</span>@endif</span><x-ui.icon name="heroicon-m-arrow-right" class="size-5 shrink-0 text-fg-muted transition group-hover:translate-x-0.5 group-hover:text-brand" /></a></li>
            @endforeach
        </ul>
    @else
        <x-ui.grid :cols="3">
            @foreach ($data['services'] as $service)
                <x-cards.service :service="$service" :show-category="$category === null" />
            @endforeach
        </x-ui.grid>
    @endif
</x-ui.section>
