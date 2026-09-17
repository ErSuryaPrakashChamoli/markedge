<x-layouts.app :meta="$meta">
    <x-sections.entity-hero eyebrow="Documentation" :title="$product->name.' documentation'" :intro="'Guides and reference for '.$product->name.'.'" :breadcrumbs="[['label' => 'Products', 'url' => '/products'], ['label' => $product->name, 'url' => '/products/'.$product->slug], ['label' => 'Documentation', 'url' => null]]" />

    <x-ui.section>
        <div class="space-y-12">
            @foreach ($sections as $section => $documents)
                <div>
                    <h2 class="text-h3 mb-4">{{ $section }}</h2>
                    <ul class="divide-y divide-line">
                        @foreach ($documents as $document)
                            <li class="py-4">
                                <a href="{{ url('/products/'.$product->slug.'/docs/'.$document->slug) }}" class="text-h4 text-fg hover:text-brand">{{ $document->title }}</a>
                                @if ($document->excerpt)<p class="mt-1 text-body-sm text-fg-secondary">{{ $document->excerpt }}</p>@endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    </x-ui.section>

    @if ($cta)
        <x-cta.band :cta="$cta" :entity="$product->name" />
    @endif
</x-layouts.app>
