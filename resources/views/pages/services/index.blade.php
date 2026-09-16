<x-layouts.app :meta="$meta">
    <x-sections.entity-hero eyebrow="Build. Operate. Grow." title="Services" intro="Software development, IT infrastructure and digital growth, delivered by one technology partner." :breadcrumbs="[['label' => 'Services', 'url' => null]]" />

    @forelse ($categories as $category)
        <x-ui.section :theme="$loop->odd ? 'light' : 'neutral'" :id="$category->slug">
            <div class="mb-10 flex flex-wrap items-end justify-between gap-4">
                <x-ui.section-header :eyebrow="$category->pillar_label" :title="$category->name" :intro="$category->tagline ?: $category->short_description" />
                <x-ui.arrow-link href="{{ url('/services/'.$category->slug) }}">About {{ $category->name }}</x-ui.arrow-link>
            </div>
            @if ($category->services->isNotEmpty())
                <x-ui.grid :cols="3">
                    @foreach ($category->services as $service)
                        <x-cards.service :service="$service" />
                    @endforeach
                </x-ui.grid>
            @else
                <p class="text-body text-fg-muted">Service pages for this area are being prepared.</p>
            @endif
        </x-ui.section>
    @empty
        <x-ui.section container="narrow">
            <p class="text-body-lg text-fg-secondary">Service pages are being prepared. In the meantime, get in touch to discuss your requirement.</p>
        </x-ui.section>
    @endforelse

    @if ($cta)
        <x-cta.band :cta="$cta" />
    @endif
</x-layouts.app>
