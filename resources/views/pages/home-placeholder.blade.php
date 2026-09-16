{{-- Temporary home view proving the layout end to end. Replaced by the CMS-driven home page in Phase 5. --}}
@inject('ctas', 'App\Services\Cms\CtaResolver')
@php $cta = $ctas->byKey('start-conversation'); @endphp
<x-layouts.app description="Markedge Technologies builds software, operates IT infrastructure and grows businesses digitally.">
    <x-ui.section theme="dark" pattern="grid">
        <div class="max-w-3xl py-8 lg:py-16">
            <x-ui.eyebrow>Build. Operate. Grow.</x-ui.eyebrow>
            <h1 class="mt-4 text-display">Technology that moves business forward.</h1>
            <p class="mt-6 max-w-2xl text-body-lg text-fg-secondary">[PLACEHOLDER: supporting message covering software, IT infrastructure, digital growth and business products]</p>
            <div class="mt-8 flex flex-wrap gap-3">
                @if ($cta)
                    <x-cta.button :cta="$cta" size="lg" />
                @endif
                <x-ui.button href="{{ url('/services') }}" variant="outline" size="lg" icon="heroicon-m-arrow-right">Explore Our Capabilities</x-ui.button>
            </div>
        </div>
    </x-ui.section>
</x-layouts.app>
