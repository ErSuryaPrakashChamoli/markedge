<x-layouts.app :meta="$meta">
    <x-sections.entity-hero eyebrow="Work" title="Case studies" intro="Selected work, described as it happened." :breadcrumbs="[['label' => 'Case Studies', 'url' => null]]" />

    @if ($caseStudies->isNotEmpty())
        <x-ui.section>
            <x-ui.grid :cols="3">
                @foreach ($caseStudies as $caseStudy)<x-cards.case-study :case-study="$caseStudy" />@endforeach
            </x-ui.grid>
        </x-ui.section>
    @else
        <x-ui.section container="narrow">
            <h2 class="text-h3">Case studies will be published here</h2>
            <p class="mt-3 text-body-lg text-fg-secondary">We publish work only with the client's agreement. Until then, explore our services and products, or talk to us about your requirement.</p>
            <div class="mt-6 flex flex-wrap gap-3">
                <x-ui.button href="{{ url('/services') }}" variant="outline">Explore services</x-ui.button>
                <x-ui.button href="{{ url('/products') }}" variant="outline">View products</x-ui.button>
            </div>
        </x-ui.section>
    @endif

    @if ($cta)
        <x-cta.band :cta="$cta" />
    @endif
</x-layouts.app>
