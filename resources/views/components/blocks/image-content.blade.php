@props(['data', 'host' => null, 'preview' => false])
<x-ui.section :theme="$data['theme'] ?? 'light'" :id="$data['anchor'] ?? null" :container="($data['layout'] ?? 'contained') === 'full' ? 'full' : 'default'" spacing="sm">
    @if (filled($data['heading'] ?? null))
        <h2 class="mb-6 text-h2">{{ $data['heading'] }}</h2>
    @endif
    <figure>
        <img src="{{ $data['imageUrl'] }}" alt="{{ $data['image_alt'] }}" class="h-auto w-full rounded-card object-cover" loading="lazy" decoding="async">
        @if (filled($data['caption'] ?? null))
            <figcaption class="mt-3 text-caption text-fg-muted">{{ $data['caption'] }}</figcaption>
        @endif
    </figure>
    @if (filled(strip_tags((string) ($data['body'] ?? ''))))
        <x-ui.prose :html="$data['body']" class="mt-6" />
    @endif
</x-ui.section>
