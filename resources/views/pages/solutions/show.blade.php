{{-- Solution skeleton (architecture §11). --}}
<x-layouts.app :meta="$meta" :preview="$preview">
    <x-sections.entity-hero eyebrow="Solution" :title="$entity->name" :intro="$entity->tagline ?: $entity->short_description" :breadcrumbs="$breadcrumbs" :media="$entity->getFirstMedia('hero')" :cta="$cta" :entity-name="$entity->name" />

    <x-sections.rich-content :html="$entity->problem_statement" heading="The problem" />
    <x-sections.rich-content :html="$entity->approach" heading="How Markedge solves it" theme="neutral" />

    <x-sections.related-grid :items="$entity->services" heading="Services that deliver this solution" card="service" :cols="min(max($entity->services->count(), 2), 3)" />
    <x-sections.related-grid :items="$entity->products" heading="Products that accelerate it" card="product" :cols="min(max($entity->products->count(), 2), 3)" theme="neutral" />

    <x-sections.feature-list :items="collect($entity->outcomes ?? [])->map(fn ($o) => ['title' => $o['label'] ?? null, 'text' => $o['text'] ?? null])->all()" heading="What changes" :columns="2" id="outcomes" />

    <x-sections.technology-strip :technologies="$entity->technologies" />

    <x-cms.blocks :blocks="$blocks" :host="$entity" :preview="$preview" />

    <x-sections.related-grid :items="$entity->industries" heading="Where this applies" card="industry" :cols="4" />
    <x-sections.related-grid :items="$related['caseStudies']" heading="Related work" card="case-study" theme="neutral" />
    <x-sections.related-grid :items="$related['articles']" heading="Insights" card="article" />
    <x-sections.faq-list :faqs="$entity->faqs" theme="neutral" />

    @if ($cta)
        <x-cta.band :cta="$cta" :entity="$entity->name" />
    @endif
</x-layouts.app>
