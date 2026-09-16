<x-layouts.app :meta="$meta">
    <x-sections.entity-hero eyebrow="Industries" title="Technology for businesses across industries." intro="How Markedge's software, infrastructure and growth capabilities apply to your sector." :breadcrumbs="[['label' => 'Industries', 'url' => null]]" />

    @if ($industries->isNotEmpty())
        <x-ui.section>
            <x-ui.grid :cols="4">
                @foreach ($industries as $industry)<x-cards.industry :industry="$industry" />@endforeach
            </x-ui.grid>
        </x-ui.section>
    @else
        <x-ui.section container="narrow"><p class="text-body-lg text-fg-secondary">Industry pages are being prepared. Get in touch to discuss your sector.</p></x-ui.section>
    @endif

    @if ($cta)
        <x-cta.band :cta="$cta" />
    @endif
</x-layouts.app>
