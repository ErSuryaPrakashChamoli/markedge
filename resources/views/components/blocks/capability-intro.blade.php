@props(['data', 'host' => null, 'preview' => false])
@inject('urls', 'App\Services\Cms\PublicUrl')
@php $cards = $data['categories']->count() + ($data['products']->isNotEmpty() ? 1 : 0); @endphp
<x-ui.section :theme="$data['theme'] ?? 'light'" :id="$data['anchor'] ?? null">
    <x-ui.section-header :title="$data['heading']" :intro="$data['intro'] ?? null" class="mb-10" />
    <x-ui.grid :cols="$cards >= 4 ? 4 : 3">
        @foreach ($data['categories'] as $category)
            <x-ui.card :href="$urls->pathFor($category)">
                @if ($category->pillar_label)
                    <x-ui.eyebrow>{{ $category->pillar_label }}</x-ui.eyebrow>
                @endif
                <h3 class="mt-3 text-h3">{{ $category->name }}</h3>
                @if ($category->tagline ?? $category->short_description)
                    <p class="mt-2 text-body-sm text-fg-secondary">{{ $category->tagline ?: \Illuminate\Support\Str::limit($category->short_description, 140) }}</p>
                @endif
                @if ($category->services_count > 0)
                    <p class="mt-4 text-caption text-fg-muted">{{ $category->services_count }} {{ \Illuminate\Support\Str::plural('service', $category->services_count) }}</p>
                @endif
            </x-ui.card>
        @endforeach
        @if ($data['products']->isNotEmpty())
            <x-ui.card href="{{ url('/products') }}">
                <x-ui.eyebrow>Products</x-ui.eyebrow>
                <h3 class="mt-3 text-h3">Business platforms</h3>
                <ul class="mt-3 space-y-1 text-body-sm text-fg-secondary">
                    @foreach ($data['products']->take(4) as $product)
                        <li>{{ $product->name }}</li>
                    @endforeach
                </ul>
            </x-ui.card>
        @endif
    </x-ui.grid>
</x-ui.section>
