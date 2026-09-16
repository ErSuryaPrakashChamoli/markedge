@props(['data', 'host' => null, 'preview' => false])
@inject('urls', 'App\Services\Cms\PublicUrl')
<x-ui.section :theme="$data['theme'] ?? 'light'" :id="$data['anchor'] ?? null">
    <div class="mb-10 flex flex-wrap items-end justify-between gap-4">
        <x-ui.section-header eyebrow="Products" :title="filled($data['heading'] ?? null) ? $data['heading'] : 'Markedge Products'" :intro="$data['intro'] ?? null" />
        <x-ui.arrow-link href="{{ url('/products') }}">{{ filled($data['cta_label'] ?? null) ? $data['cta_label'] : 'Explore products' }}</x-ui.arrow-link>
    </div>
    @if (($data['layout'] ?? 'alternating') === 'grid')
        <x-ui.grid :cols="min(max($data['products']->count(), 2), 3)">
            @foreach ($data['products'] as $product)
                <x-cards.product :product="$product" />
            @endforeach
        </x-ui.grid>
    @else
        <div class="space-y-12">
            @foreach ($data['products'] as $product)
                @php $hero = $product->getFirstMedia('hero'); $logo = $product->getFirstMedia('logo'); @endphp
                <x-ui.split :ratio="$loop->even ? 'wide-right' : 'wide-left'" :reverse-on-mobile="$loop->even">
                    <x-slot:left>
                        @if ($loop->even)
                            <x-ui.picture :media="$hero" conversion="hero" sizes="(min-width: 1024px) 50vw, 100vw" :placeholder-label="$hero ? null : $product->name" />
                        @else
                            <x-blocks.partials.product-summary :product="$product" :logo="$logo" />
                        @endif
                    </x-slot:left>
                    <x-slot:right>
                        @if ($loop->even)
                            <x-blocks.partials.product-summary :product="$product" :logo="$logo" />
                        @else
                            <x-ui.picture :media="$hero" conversion="hero" sizes="(min-width: 1024px) 50vw, 100vw" :placeholder-label="$hero ? null : $product->name" />
                        @endif
                    </x-slot:right>
                </x-ui.split>
            @endforeach
        </div>
    @endif
</x-ui.section>
