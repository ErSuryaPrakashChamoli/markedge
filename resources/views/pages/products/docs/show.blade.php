{{-- Product documentation page (Phase 15). --}}
<x-layouts.app :meta="$meta" :preview="$preview">
    <x-sections.entity-hero :eyebrow="$entity->section ?: 'Documentation'" :title="$entity->title" :intro="$entity->excerpt" :breadcrumbs="$breadcrumbs" />

    <x-ui.section>
        <x-ui.split ratio="wide-left" align="start">
            <x-slot:left>
                @if (filled(strip_tags((string) $entity->body)))
                    <x-ui.prose :html="$entity->body" />
                @else
                    <p class="text-body text-fg-secondary">This page has no content yet.</p>
                @endif
                <p class="mt-10 text-caption text-fg-muted">Last updated {{ $entity->updated_at->format('d M Y') }}.</p>
            </x-slot:left>
            <x-slot:right>
                @if ($related['documents']->isNotEmpty())
                    <nav aria-label="{{ $entity->product->name }} documentation" class="rounded-card border border-line bg-surface p-5">
                        <p class="text-eyebrow text-fg-muted">In this documentation</p>
                        <ul class="mt-3 space-y-2 text-body-sm">
                            @foreach ($related['documents'] as $document)
                                <li><a href="{{ url('/products/'.$entity->product->slug.'/docs/'.$document->slug) }}" @class(['hover:text-brand', 'font-semibold text-brand' => $document->id === $entity->id, 'text-fg' => $document->id !== $entity->id])>{{ $document->title }}</a></li>
                            @endforeach
                        </ul>
                        <p class="mt-4"><x-ui.arrow-link :href="url('/products/'.$entity->product->slug.'/docs')">All documentation</x-ui.arrow-link></p>
                    </nav>
                @endif
                <p class="mt-6 text-body-sm text-fg-secondary">Documentation for <a href="{{ url('/products/'.$entity->product->slug) }}" class="font-medium text-fg hover:text-brand">{{ $entity->product->name }}</a>.</p>
            </x-slot:right>
        </x-ui.split>
    </x-ui.section>

    @if ($cta)
        <x-cta.band :cta="$cta" :entity="$entity->product->name" />
    @endif
</x-layouts.app>
