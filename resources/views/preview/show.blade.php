{{-- Generic draft preview. Entity-specific templates replace the body in Phase 5; this view never emits canonical, schema or analytics. --}}
@php
    $title = $record->title ?? $record->name ?? 'Preview';
    $summary = $record->excerpt ?? $record->short_description ?? $record->tagline ?? null;
    $status = $record->status?->getLabel() ?? 'Draft';
@endphp
<x-layouts.app :title="'Preview: '.$title" robots="noindex, nofollow, noarchive" :show-sticky-cta="false">
    <div data-theme="dark" class="bg-brand text-white">
        <x-ui.container class="flex flex-wrap items-center justify-between gap-3 py-2 text-body-sm">
            <p><strong>Preview</strong> — {{ ucfirst(str_replace('-', ' ', $type)) }} · Status: {{ $status }}. This link expires and is not indexed.</p>
            <p class="text-caption">Forms and tracking are disabled in preview.</p>
        </x-ui.container>
    </div>

    <x-ui.section theme="dark" pattern="grid" spacing="sm">
        <x-ui.eyebrow>{{ ucfirst(str_replace('-', ' ', $type)) }}</x-ui.eyebrow>
        <h1 class="mt-3 text-h1">{{ $title }}</h1>
        @if ($summary)
            <p class="mt-4 max-w-2xl text-body-lg text-fg-secondary">{{ $summary }}</p>
        @endif
    </x-ui.section>

    @if (method_exists($record, 'enabledBlocks'))
        <x-cms.blocks :blocks="$record->enabledBlocks()" :host="$record" preview />
    @endif

    @foreach (['overview', 'long_description', 'description', 'body', 'problem_statement', 'approach', 'challenge', 'solution', 'implementation', 'results'] as $field)
        @if (filled($record->{$field} ?? null))
            <x-ui.section spacing="sm" container="prose">
                <h2 class="text-h3 mb-4">{{ ucfirst(str_replace('_', ' ', $field)) }}</h2>
                <x-ui.prose :html="$record->{$field}" />
            </x-ui.section>
        @endif
    @endforeach
</x-layouts.app>
