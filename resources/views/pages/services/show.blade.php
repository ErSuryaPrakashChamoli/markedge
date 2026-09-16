{{-- Service skeleton (architecture §10.2). Every section hides itself when its data is empty. --}}
<x-layouts.app :meta="$meta" :preview="$preview">
    <x-sections.entity-hero :eyebrow="$entity->category?->name" :title="$entity->name" :intro="$entity->tagline ?: $entity->short_description" :breadcrumbs="$breadcrumbs" :media="$entity->getFirstMedia('hero')" :cta="$cta" :entity-name="$entity->name" />

    <x-sections.rich-content :html="$entity->overview" heading="Overview" />

    <x-sections.feature-list :items="$entity->benefits ?? []" heading="Benefits" theme="neutral" id="benefits" />
    <x-sections.feature-list :items="$entity->features ?? []" heading="What we deliver" id="capabilities" />
    <x-sections.process-steps :steps="$entity->process ?? []" heading="How we work" theme="neutral" id="process" />

    @if (filled($entity->deliverables))
        <x-ui.section container="narrow" spacing="sm">
            <h2 class="text-h3 mb-4">Deliverables</h2>
            <ul class="grid gap-2 sm:grid-cols-2">
                @foreach ($entity->deliverables as $deliverable)
                    @if (filled($deliverable))<li class="flex items-start gap-2 text-body text-fg"><x-ui.icon name="heroicon-m-check" class="mt-1 size-4 text-brand" />{{ $deliverable }}</li>@endif
                @endforeach
            </ul>
        </x-ui.section>
    @endif

    <x-sections.technology-strip :technologies="$entity->technologies" />

    <x-cms.blocks :blocks="$blocks" :host="$entity" :preview="$preview" />

    <x-sections.related-grid :items="$related['industries']" heading="Industries" card="industry" :cols="4" />
    <x-sections.related-grid :items="$related['solutions']" heading="Solutions this service supports" card="solution" theme="neutral" />
    <x-sections.related-grid :items="$related['products']" heading="Products that support this service" card="product" :cols="min(max($related['products']->count(), 2), 3)" />
    <x-sections.related-grid :items="$related['caseStudies']" heading="Related work" card="case-study" theme="neutral" />
    <x-sections.related-grid :items="$related['articles']" heading="Insights" card="article" />

    <x-sections.faq-list :faqs="$entity->faqs" theme="neutral" />

    <x-sections.related-grid :items="$related['services']" heading="Related services" card="service" :cols="min(max($related['services']->count(), 2), 4)" />

    @if ($cta)
        <x-cta.band :cta="$cta" :entity="$entity->name" />
    @endif
</x-layouts.app>
