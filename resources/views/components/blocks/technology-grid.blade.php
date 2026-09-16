@props(['data', 'host' => null, 'preview' => false])
<x-ui.section :theme="$data['theme'] ?? 'neutral'" :id="$data['anchor'] ?? null">
    <x-ui.section-header :title="filled($data['heading'] ?? null) ? $data['heading'] : 'Engineering & Technology'" :intro="$data['intro'] ?? null" class="mb-10" />
    @if (($data['display'] ?? 'logos') === 'list')
        <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($data['groups'] as $group)
                <div>
                    <h3 class="text-eyebrow text-fg-muted">{{ $group['label'] }}</h3>
                    <ul class="mt-3 space-y-1.5 text-body text-fg">
                        @foreach ($group['items'] as $technology)<li>{{ $technology->name }}</li>@endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    @else
        <div class="space-y-8">
            @foreach ($data['groups'] as $group)
                <div class="grid gap-3 md:grid-cols-[10rem_1fr]">
                    <h3 class="text-eyebrow text-fg-muted md:pt-3">{{ $group['label'] }}</h3>
                    <ul class="flex flex-wrap gap-3">
                        @foreach ($group['items'] as $technology)<li><x-cards.technology :technology="$technology" /></li>@endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    @endif
</x-ui.section>
