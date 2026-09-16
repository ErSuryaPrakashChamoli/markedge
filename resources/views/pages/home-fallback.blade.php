{{-- Rendered only while no home page is published. Structural, claim-free, built from real records. --}}
<x-layouts.app :meta="$meta">
    <x-ui.section theme="dark" pattern="grid">
        <div class="grid grid-cols-1 gap-10 lg:grid-cols-2 lg:items-center">
            <div class="max-w-3xl py-8 lg:py-16">
                <x-ui.eyebrow>Build. Operate. Grow.</x-ui.eyebrow>
                <h1 class="mt-4 text-display">Technology that moves business forward.</h1>
                <p class="mt-6 max-w-2xl text-body-lg text-fg-secondary">Software, IT infrastructure, digital growth and business products from one technology partner.</p>
                @if ($cta)
                    <div class="mt-8 flex flex-wrap gap-3">
                        <x-cta.button :cta="$cta" size="lg" />
                        <x-ui.button href="{{ url('/services') }}" variant="outline" size="lg" icon="heroicon-m-arrow-right">Explore Our Capabilities</x-ui.button>
                    </div>
                @endif
            </div>
            <x-blocks.partials.ecosystem-visual class="hidden lg:block" />
        </div>
    </x-ui.section>

    @if ($categories->isNotEmpty())
        <x-ui.section>
            <x-ui.section-header title="One technology partner. Multiple capabilities." class="mb-10" />
            <x-ui.grid :cols="$categories->count() + ($products->isNotEmpty() ? 1 : 0) >= 4 ? 4 : 3">
                @foreach ($categories as $category)
                    <x-ui.card href="{{ url('/services/'.$category->slug) }}">
                        @if ($category->pillar_label)<x-ui.eyebrow>{{ $category->pillar_label }}</x-ui.eyebrow>@endif
                        <h2 class="mt-3 text-h3">{{ $category->name }}</h2>
                        @if ($category->tagline)<p class="mt-2 text-body-sm text-fg-secondary">{{ $category->tagline }}</p>@endif
                        @if ($category->services_count > 0)<p class="mt-4 text-caption text-fg-muted">{{ $category->services_count }} {{ \Illuminate\Support\Str::plural('service', $category->services_count) }}</p>@endif
                    </x-ui.card>
                @endforeach
                @if ($products->isNotEmpty())
                    <x-ui.card href="{{ url('/products') }}">
                        <x-ui.eyebrow>Products</x-ui.eyebrow>
                        <h2 class="mt-3 text-h3">Business platforms</h2>
                        <ul class="mt-3 space-y-1 text-body-sm text-fg-secondary">@foreach ($products->take(4) as $product)<li>{{ $product->name }}</li>@endforeach</ul>
                    </x-ui.card>
                @endif
            </x-ui.grid>
        </x-ui.section>
    @endif

    @if ($products->isNotEmpty())
        <x-sections.related-grid :items="$products" heading="Markedge Products" card="product" :cols="min(max($products->count(), 2), 3)" theme="neutral" more-label="Explore products" :more-href="url('/products')" />
    @endif

    @if ($cta)
        <x-cta.band :cta="$cta" />
    @endif
</x-layouts.app>
