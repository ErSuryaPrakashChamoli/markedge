@props(['data', 'host' => null, 'preview' => false])
<x-ui.section :theme="$data['theme'] ?? 'light'" :id="$data['anchor'] ?? null" container="narrow" spacing="sm">
    @if (filled($data['heading'] ?? null))
        <h2 class="mb-6 text-h2">{{ $data['heading'] }}</h2>
    @endif
    <figure x-data="{ playing: false }">
        <div class="relative aspect-video overflow-hidden rounded-card border border-line bg-canvas-dark">
            <template x-if="! playing">
                <button type="button" @click="playing = true" class="group absolute inset-0 flex items-center justify-center" aria-label="Play video">
                    @if ($data['posterUrl'])
                        <img src="{{ $data['posterUrl'] }}" alt="" class="absolute inset-0 size-full object-cover" loading="lazy" decoding="async">
                    @endif
                    <span class="relative flex size-16 items-center justify-center rounded-pill bg-brand text-white transition group-hover:bg-brand-hover"><x-ui.icon name="heroicon-s-play" class="size-7" /></span>
                </button>
            </template>
            <template x-if="playing">
                <iframe src="{{ $data['embedUrl'] }}?autoplay=1" title="{{ $data['caption'] ?? $data['heading'] ?? 'Video' }}" class="absolute inset-0 size-full" allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen loading="lazy" referrerpolicy="strict-origin-when-cross-origin"></iframe>
            </template>
        </div>
        @if (filled($data['caption'] ?? null))
            <figcaption class="mt-3 text-caption text-fg-muted">{{ $data['caption'] }}</figcaption>
        @endif
    </figure>
</x-ui.section>
