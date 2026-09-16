@props(['data', 'host' => null, 'preview' => false])
@inject('urls', 'App\Services\Cms\PublicUrl')
<x-ui.section :theme="$data['theme'] ?? 'light'" :id="$data['anchor'] ?? null">
    <x-ui.section-header :title="filled($data['heading'] ?? null) ? $data['heading'] : 'Related content'" class="mb-10" />
    <x-ui.grid :cols="3">
        @foreach ($data['items'] as $item)
            @php $type = ucfirst(str_replace('_', ' ', $item->getMorphClass())); @endphp
            <x-ui.card :href="$urls->pathFor($item)">
                <x-ui.badge class="self-start">{{ $type }}</x-ui.badge>
                <h3 class="mt-3 text-h4">{{ $item->title ?? $item->name }}</h3>
                @if ($item->excerpt ?? $item->short_description ?? $item->tagline ?? null)
                    <p class="mt-2 text-body-sm text-fg-secondary">{{ \Illuminate\Support\Str::limit($item->excerpt ?? $item->short_description ?? $item->tagline, 140) }}</p>
                @endif
            </x-ui.card>
        @endforeach
    </x-ui.grid>
</x-ui.section>
