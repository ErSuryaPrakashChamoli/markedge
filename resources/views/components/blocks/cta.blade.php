@props(['data', 'host' => null, 'preview' => false])
<x-ui.section :theme="$data['theme'] ?? 'dark'" :id="$data['anchor'] ?? null" spacing="sm">
    <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
        <div class="max-w-2xl">
            <p class="text-h2">{{ filled($data['heading'] ?? null) ? $data['heading'] : ($data['cta']->headline ?? $data['cta']->primary_label) }}</p>
            @if (filled($data['body'] ?? null) || filled($data['cta']->body))
                <p class="mt-3 text-body-lg text-fg-secondary">{{ filled($data['body'] ?? null) ? $data['body'] : $data['cta']->body }}</p>
            @endif
        </div>
        <div class="flex flex-wrap gap-3">
            @foreach (array_filter($data['links']) as $link)
                <x-ui.button :href="$link['href']" :variant="$loop->first ? 'primary' : 'outline'" size="lg" :target="$link['external'] ? '_blank' : null" :rel="$link['external'] ? 'noopener' : null">{{ $link['label'] }}</x-ui.button>
            @endforeach
        </div>
    </div>
</x-ui.section>
