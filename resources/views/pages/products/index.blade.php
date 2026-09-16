<x-layouts.app :meta="$meta">
    <x-sections.entity-hero eyebrow="Products" title="Markedge Products" intro="Business platforms that solve recurring operational problems, built and supported by Markedge." :breadcrumbs="[['label' => 'Products', 'url' => null]]" />

    @if ($products->isNotEmpty())
        <x-ui.section>
            <div class="space-y-12">
                @foreach ($products as $product)
                    @php $hero = $product->getFirstMedia('hero'); @endphp
                    <x-ui.split :ratio="$loop->even ? 'wide-right' : 'wide-left'" :reverse-on-mobile="$loop->even">
                        <x-slot:left>
                            @if ($loop->even)
                                <x-ui.picture :media="$hero" conversion="hero" :priority="$loop->first" sizes="(min-width: 1024px) 50vw, 100vw" :placeholder-label="$hero ? null : $product->name" />
                            @else
                                <x-blocks.partials.product-summary :product="$product" :logo="$product->getFirstMedia('logo')" />
                            @endif
                        </x-slot:left>
                        <x-slot:right>
                            @if ($loop->even)
                                <x-blocks.partials.product-summary :product="$product" :logo="$product->getFirstMedia('logo')" />
                            @else
                                <x-ui.picture :media="$hero" conversion="hero" :priority="$loop->first" sizes="(min-width: 1024px) 50vw, 100vw" :placeholder-label="$hero ? null : $product->name" />
                            @endif
                        </x-slot:right>
                    </x-ui.split>
                    @unless ($loop->last)<x-ui.divider />@endunless
                @endforeach
            </div>
        </x-ui.section>
    @else
        <x-ui.section container="narrow">
            <p class="text-body-lg text-fg-secondary">Product pages are being prepared. Get in touch to hear about what we are building.</p>
        </x-ui.section>
    @endif

    @if ($cta)
        <x-cta.band :cta="$cta" />
    @endif
</x-layouts.app>
