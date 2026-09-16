<x-layouts.app :meta="$meta" :preview="$preview">
    <x-sections.entity-hero :eyebrow="$entity->pillar_label" :title="$entity->name" :intro="$entity->tagline ?: $entity->short_description" :breadcrumbs="$breadcrumbs" :media="$entity->getFirstMedia('hero')" :cta="$cta" :entity-name="$entity->name" />

    <x-sections.rich-content :html="$entity->description" />

    @if ($entity->services->isNotEmpty())
        <x-ui.section theme="neutral">
            <x-ui.section-header :title="$entity->name.' services'" class="mb-10" />
            <x-ui.grid :cols="3">
                @foreach ($entity->services as $service)
                    <x-cards.service :service="$service" />
                @endforeach
            </x-ui.grid>
        </x-ui.section>
    @endif

    <x-cms.blocks :blocks="$blocks" :host="$entity" :preview="$preview" />

    <x-sections.related-grid :items="$related['products']" heading="Products that support these services" card="product" :cols="min(max($related['products']->count(), 2), 3)" />

    <x-sections.faq-list :faqs="$entity->faqs" />

    @if ($cta)
        <x-cta.band :cta="$cta" :entity="$entity->name" />
    @endif
</x-layouts.app>
