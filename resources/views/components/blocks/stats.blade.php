@props(['data', 'host' => null, 'preview' => false])
<x-ui.section :theme="$data['theme'] ?? 'neutral'" :id="$data['anchor'] ?? null" spacing="sm">
    @if (filled($data['heading'] ?? null))
        <x-ui.section-header :title="$data['heading']" class="mb-8" />
    @endif
    <div class="grid grid-cols-2 gap-8 md:grid-cols-{{ min(count($data['items']), 4) }}">
        @foreach ($data['items'] as $item)
            <x-ui.stat :value="$item['value']" :label="$item['label']" :note="$item['note'] ?? null" />
        @endforeach
    </div>
    @if (filled($data['source_note'] ?? null))
        <p class="mt-6 text-caption text-fg-muted">{{ $data['source_note'] }}</p>
    @endif
</x-ui.section>
