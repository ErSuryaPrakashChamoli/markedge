{{-- Generic product skeleton (architecture §9.1). LMS, RMS and every future product render through this file. --}}
@inject('urls', 'App\Services\Cms\PublicUrl')
@php $screenshots = $entity->getMedia('screenshots'); $grouped = $entity->features->whereNull('product_module_id')->groupBy(fn ($feature) => $feature->group_label ?: ''); $hasDocs = ($entity->published_documents_count ?? 0) > 0; @endphp
<x-layouts.app :meta="$meta" :preview="$preview">
    <x-sections.entity-hero eyebrow="Product" :title="$entity->name" :intro="$entity->tagline" :breadcrumbs="$breadcrumbs" :media="$entity->getFirstMedia('hero')" :cta="$cta" :entity-name="$entity->name" :badge="$entity->isComingSoon() ? 'Coming soon' : null">
        @if ($entity->short_description)
            <p class="mt-4 max-w-2xl text-body text-fg-secondary">{{ $entity->short_description }}</p>
        @endif
    </x-sections.entity-hero>

    <x-sections.rich-content :html="$entity->long_description" heading="Overview" />

    @if ($grouped->isNotEmpty())
        <x-ui.section theme="neutral" id="features">
            <x-ui.section-header title="Features" class="mb-10" />
            <div class="space-y-12">
                @foreach ($grouped as $group => $features)
                    <div>
                        @if ($group !== '')<h3 class="mb-6 text-eyebrow text-brand">{{ $group }}</h3>@endif
                        <x-ui.grid :cols="3">
                            @foreach ($features as $feature)
                                <div class="border-t-2 border-line pt-5">
                                    @if ($feature->icon && preg_match('/^heroicon-[a-z]-[a-z0-9-]+$/', $feature->icon))<x-ui.icon :name="$feature->icon" class="mb-3 size-6 text-brand" />@endif
                                    <h4 class="text-h4">{{ $feature->title }}</h4>
                                    @if ($feature->description)<p class="mt-2 text-body-sm text-fg-secondary">{{ $feature->description }}</p>@endif
                                    <x-products.capabilities :capabilities="$feature->capabilities" />
                                </div>
                            @endforeach
                        </x-ui.grid>
                    </div>
                @endforeach
            </div>
        </x-ui.section>
    @endif

    @if ($entity->modules->isNotEmpty())
        <x-ui.section id="modules">
            <x-ui.section-header title="Modules" class="mb-10" />
            <div class="space-y-16">
                @foreach ($entity->modules as $module)
                    @php $image = $module->getFirstMedia('image'); @endphp
                    <x-ui.split :ratio="$image ? 'equal' : 'wide-left'" align="start" :reverse-on-mobile="$loop->even && $image !== null">
                        <x-slot:left>
                            @if ($loop->even && $image)
                                <x-ui.picture :media="$image" conversion="card" sizes="(min-width: 1024px) 50vw, 100vw" />
                            @else
                                <h3 class="text-h3">{{ $module->name }}</h3>
                                @if ($module->summary)<p class="mt-3 text-body-lg text-fg-secondary">{{ $module->summary }}</p>@endif
                                @if (filled(strip_tags((string) $module->description)))<x-ui.prose :html="$module->description" class="mt-4" />@endif
                                @if (filled($module->highlights))<ul class="mt-5 space-y-2">@foreach ($module->highlights as $highlight)@if (filled($highlight))<li class="flex items-start gap-2 text-body-sm text-fg"><x-ui.icon name="heroicon-m-check" class="mt-0.5 size-4 text-brand" />{{ $highlight }}</li>@endif @endforeach</ul>@endif
                                <x-products.module-features :features="$module->features" />
                            @endif
                        </x-slot:left>
                        <x-slot:right>
                            @if ($loop->even && $image)
                                <h3 class="text-h3">{{ $module->name }}</h3>
                                @if ($module->summary)<p class="mt-3 text-body-lg text-fg-secondary">{{ $module->summary }}</p>@endif
                                @if (filled(strip_tags((string) $module->description)))<x-ui.prose :html="$module->description" class="mt-4" />@endif
                                <x-products.module-features :features="$module->features" />
                            @elseif ($image)
                                <x-ui.picture :media="$image" conversion="card" sizes="(min-width: 1024px) 50vw, 100vw" />
                            @endif
                        </x-slot:right>
                    </x-ui.split>
                @endforeach
            </div>
        </x-ui.section>
    @endif

    <x-sections.feature-list :items="$entity->benefits ?? []" heading="Benefits" theme="neutral" id="benefits" />
    <x-sections.feature-list :items="$entity->use_cases ?? []" heading="Use cases" :columns="2" id="use-cases" />

    @if ($screenshots->isNotEmpty())
        <x-ui.section theme="neutral" id="screenshots">
            <x-ui.section-header title="Inside {{ $entity->name }}" class="mb-10" />
            <x-ui.grid :cols="min($screenshots->count(), 3)">
                @foreach ($screenshots as $screenshot)
                    <figure>
                        <x-ui.picture :media="$screenshot" conversion="card" sizes="(min-width: 1024px) 33vw, 100vw" />
                        @if ($screenshot->getCustomProperty('caption'))<figcaption class="mt-2 text-caption text-fg-muted">{{ $screenshot->getCustomProperty('caption') }}</figcaption>@endif
                    </figure>
                @endforeach
            </x-ui.grid>
        </x-ui.section>
    @endif

    @if (filled($entity->integrations))
        <x-ui.section spacing="sm" id="integrations">
            <h2 class="text-h3 mb-6">Integrations</h2>
            <ul class="flex flex-wrap gap-3">
                @foreach ($entity->integrations as $integration)
                    @if (filled($integration['name'] ?? null))
                        <li class="rounded-card border border-line bg-surface px-4 py-2 text-body-sm font-medium text-fg">
                            @if (filled($integration['url'] ?? null) && str_starts_with($integration['url'], 'http'))<a href="{{ $integration['url'] }}" target="_blank" rel="noopener nofollow">{{ $integration['name'] }}</a>@else{{ $integration['name'] }}@endif
                        </li>
                    @endif
                @endforeach
            </ul>
        </x-ui.section>
    @endif

    <x-products.fact-list :items="$entity->deployment ?? []" heading="Deployment options" id="deployment" />
    <x-products.fact-list :items="$entity->security ?? []" heading="Security" id="security" theme="neutral" />

    @if ($hasDocs)
        <x-ui.section spacing="sm" id="documentation">
            <h2 class="text-h3 mb-3">Documentation</h2>
            <p class="text-body text-fg-secondary">Guides and reference for {{ $entity->name }}.</p>
            <p class="mt-4"><x-ui.arrow-link :href="url('/products/'.$entity->slug.'/docs')">Read the documentation</x-ui.arrow-link></p>
        </x-ui.section>
    @endif

    <x-sections.technology-strip :technologies="$entity->technologies" />

    <x-cms.blocks :blocks="$blocks" :host="$entity" :preview="$preview" />

    <x-sections.related-grid :items="$entity->industries" heading="Built for" card="industry" :cols="4" />

    @if ($entity->testimonials->isNotEmpty())
        <x-ui.section theme="neutral">
            <x-ui.section-header title="What users say" class="mb-10" />
            <x-ui.grid :cols="min(max($entity->testimonials->count(), 2), 3)">
                @foreach ($entity->testimonials as $testimonial)<x-cards.testimonial :testimonial="$testimonial" />@endforeach
            </x-ui.grid>
        </x-ui.section>
    @endif

    <x-sections.related-grid :items="$entity->caseStudies" heading="Case studies" card="case-study" />
    <x-sections.faq-list :faqs="$entity->faqs" theme="neutral" />
    <x-sections.related-grid :items="$entity->solutions" heading="Solutions" card="solution" />
    <x-sections.related-grid :items="$related['services']" heading="Related services" card="service" theme="neutral" :cols="min(max($related['services']->count(), 2), 4)" />
    <x-sections.related-grid :items="$related['articles']" heading="Insights" card="article" />

    @if ($entity->demoForm && $entity->demoForm->is_active && ! $entity->isComingSoon())
        <x-sections.lead-form :form="$entity->demoForm" :context="['product_id' => $entity->id]" :heading="'Book a demo of '.$entity->name" :preview="$preview" theme="neutral" id="demo" />
    @elseif ($cta)
        <x-cta.band :cta="$cta" :entity="$entity->name" />
    @endif
</x-layouts.app>
