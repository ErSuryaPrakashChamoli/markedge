{{-- Case study skeleton (architecture §13). Outcomes render exactly as entered. --}}
@php $outcomes = collect($entity->outcomes ?? [])->filter(fn ($o) => filled($o['label'] ?? null) && filled($o['value'] ?? null)); $gallery = $entity->getMedia('gallery'); @endphp
<x-layouts.app :meta="$meta" :preview="$preview">
    <x-sections.entity-hero eyebrow="Case study" :title="$entity->title" :intro="$entity->excerpt" :breadcrumbs="$breadcrumbs" :media="$entity->getFirstMedia('hero')">
        <dl class="mt-8 flex flex-wrap gap-x-10 gap-y-4 text-body-sm">
            @if ($entity->client?->is_visible)
                <div><dt class="text-eyebrow text-fg-muted">Client</dt><dd class="mt-1 font-medium text-fg">{{ $entity->client->name }}</dd></div>
            @endif
            @if ($entity->industry?->isPublished())
                <div><dt class="text-eyebrow text-fg-muted">Industry</dt><dd class="mt-1 font-medium text-fg"><a href="{{ url('/industries/'.$entity->industry->slug) }}" class="hover:text-brand">{{ $entity->industry->name }}</a></dd></div>
            @endif
            @if ($entity->services->isNotEmpty())
                <div><dt class="text-eyebrow text-fg-muted">Services</dt><dd class="mt-1 font-medium text-fg">{{ $entity->services->pluck('name')->join(', ') }}</dd></div>
            @endif
        </dl>
    </x-sections.entity-hero>

    @if ($outcomes->isNotEmpty())
        <x-ui.section theme="neutral" spacing="sm">
            <div class="grid grid-cols-2 gap-8 md:grid-cols-{{ min($outcomes->count(), 4) }}">
                @foreach ($outcomes as $outcome)
                    <div class="border-l-2 border-brand pl-4">
                        <p class="{{ ($outcome['kind'] ?? 'qualitative') === 'quantitative' ? 'text-h1' : 'text-h3' }} text-fg">{{ $outcome['value'] }}</p>
                        <p class="mt-1 text-body-sm font-medium text-fg-secondary">{{ $outcome['label'] }}</p>
                        @if (filled($outcome['note'] ?? null))<p class="mt-0.5 text-caption text-fg-muted">{{ $outcome['note'] }}</p>@endif
                    </div>
                @endforeach
            </div>
        </x-ui.section>
    @endif

    <x-sections.rich-content :html="$entity->challenge" heading="The challenge" />
    <x-sections.rich-content :html="$entity->solution" heading="The approach and solution" theme="neutral" />
    <x-sections.rich-content :html="$entity->implementation" heading="Implementation" />
    <x-sections.rich-content :html="$entity->results" heading="Results" theme="neutral" />

    @if ($gallery->isNotEmpty())
        <x-ui.section>
            <x-ui.grid :cols="min($gallery->count(), 3)">
                @foreach ($gallery as $image)<x-ui.picture :media="$image" conversion="card" sizes="(min-width: 1024px) 33vw, 100vw" />@endforeach
            </x-ui.grid>
        </x-ui.section>
    @endif

    <x-sections.technology-strip :technologies="$entity->technologies" />

    <x-cms.blocks :blocks="$blocks" :host="$entity" :preview="$preview" />

    <x-sections.related-grid :items="$entity->services" heading="Services delivered" card="service" :cols="min(max($entity->services->count(), 2), 3)" />
    <x-sections.related-grid :items="$entity->products" heading="Products used" card="product" :cols="min(max($entity->products->count(), 2), 3)" theme="neutral" />

    @if ($cta)
        <x-cta.band :cta="$cta" />
    @endif
</x-layouts.app>
