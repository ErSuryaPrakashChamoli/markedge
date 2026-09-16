{{-- Industry skeleton (architecture §12). No experience claims: only linked records render. --}}
<x-layouts.app :meta="$meta" :preview="$preview">
    <x-sections.entity-hero eyebrow="Industry" :title="$entity->name" :intro="$entity->tagline ?: $entity->short_description" :breadcrumbs="$breadcrumbs" :media="$entity->getFirstMedia('hero')" :cta="$cta" :entity-name="$entity->name" />

    <x-sections.rich-content :html="$entity->description" />
    <x-sections.feature-list :items="$entity->challenges ?? []" heading="Business challenges" theme="neutral" :columns="2" id="challenges" />

    <x-sections.related-grid :items="$entity->solutions" heading="Solutions" card="solution" :cols="min(max($entity->solutions->count(), 2), 3)" />
    <x-sections.related-grid :items="$entity->services" heading="Services" card="service" :cols="3" theme="neutral" />
    <x-sections.related-grid :items="$entity->products" heading="Products" card="product" :cols="min(max($entity->products->count(), 2), 3)" />

    <x-sections.technology-strip :technologies="$entity->technologies" />

    <x-cms.blocks :blocks="$blocks" :host="$entity" :preview="$preview" />

    <x-sections.related-grid :items="$entity->caseStudies" heading="Work in this industry" card="case-study" theme="neutral" />
    <x-sections.related-grid :items="$related['articles']" heading="Insights" card="article" />
    <x-sections.faq-list :faqs="$entity->faqs" theme="neutral" />

    @if ($cta)
        <x-cta.band :cta="$cta" :entity="$entity->name" />
    @endif
</x-layouts.app>
