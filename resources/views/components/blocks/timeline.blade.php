@props(['data', 'host' => null, 'preview' => false])
<x-ui.section :theme="$data['theme'] ?? 'light'" :id="$data['anchor'] ?? null" container="narrow">
    @if (filled($data['heading'] ?? null))
        <x-ui.section-header :title="$data['heading']" class="mb-10" />
    @endif
    <ol class="relative border-l border-line pl-8">
        @foreach ($data['entries'] as $entry)
            <li class="relative pb-8 last:pb-0">
                <span class="absolute top-1.5 -left-[2.35rem] size-3 rounded-pill bg-brand ring-4 ring-canvas" aria-hidden="true"></span>
                <p class="text-eyebrow text-fg-muted">{{ $entry['date_label'] }}</p>
                <h3 class="mt-1 text-h4">{{ $entry['title'] }}</h3>
                @if (filled($entry['text'] ?? null))
                    <p class="mt-2 text-body-sm text-fg-secondary">{{ $entry['text'] }}</p>
                @endif
            </li>
        @endforeach
    </ol>
</x-ui.section>
