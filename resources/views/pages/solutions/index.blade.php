<x-layouts.app :meta="$meta">
    <x-sections.entity-hero eyebrow="Solutions" title="What are you trying to solve?" intro="Start from the business problem. Each solution connects the services and products that address it." :breadcrumbs="[['label' => 'Solutions', 'url' => null]]" />

    @if ($solutions->isNotEmpty())
        <x-ui.section id="by-business-need">
            <x-ui.section-header title="By business need" class="mb-10" />
            <x-ui.grid :cols="$solutions->count() >= 4 ? 4 : 3">
                @foreach ($solutions as $solution)<x-cards.solution :solution="$solution" />@endforeach
            </x-ui.grid>
        </x-ui.section>
    @else
        <x-ui.section container="narrow"><p class="text-body-lg text-fg-secondary">Solution pages are being prepared. Explore our services or get in touch to discuss your requirement.</p></x-ui.section>
    @endif

    <x-sections.related-grid :items="$industries" heading="By industry" card="industry" :cols="4" theme="neutral" id="by-industry" more-label="All industries" :more-href="url('/industries')" />

    @if ($cta)
        <x-cta.band :cta="$cta" />
    @endif
</x-layouts.app>
