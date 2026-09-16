@props(['data', 'host' => null, 'preview' => false])
@php $center = ($data['alignment'] ?? 'left') === 'center'; $variant = $data['variant'] ?? 'ecosystem'; @endphp
<x-ui.section :theme="$data['theme'] ?? 'dark'" :pattern="$variant === 'ecosystem' ? 'grid' : 'none'" :id="$data['anchor'] ?? null" as="div">
    <div @class(['grid grid-cols-1 gap-10 lg:grid-cols-2 lg:items-center' => $variant === 'image' && $data['imageUrl'], 'mx-auto text-center' => $center])>
        <div @class(['max-w-3xl py-8 lg:py-16', 'mx-auto' => $center])>
            @if (filled($data['eyebrow'] ?? null))
                <x-ui.eyebrow>{{ $data['eyebrow'] }}</x-ui.eyebrow>
            @endif
            <h1 class="mt-4 text-display">{{ $data['headline'] }}</h1>
            @if (filled($data['subheading'] ?? null))
                <p class="mt-6 max-w-2xl text-body-lg text-fg-secondary {{ $center ? 'mx-auto' : '' }}">{{ $data['subheading'] }}</p>
            @endif
            @if ($data['primaryCta'] || $data['secondaryCta'])
                <div class="mt-8 flex flex-wrap gap-3 {{ $center ? 'justify-center' : '' }}">
                    @if ($data['primaryCta'])
                        <x-ui.button :href="$data['primaryCta']['href']" size="lg" :target="$data['primaryCta']['external'] ? '_blank' : null" :rel="$data['primaryCta']['external'] ? 'noopener' : null">{{ $data['primaryCta']['label'] }}</x-ui.button>
                    @endif
                    @if ($data['secondaryCta'])
                        <x-ui.button :href="$data['secondaryCta']['href']" variant="outline" size="lg" icon="heroicon-m-arrow-right" :target="$data['secondaryCta']['external'] ? '_blank' : null" :rel="$data['secondaryCta']['external'] ? 'noopener' : null">{{ $data['secondaryCta']['label'] }}</x-ui.button>
                    @endif
                </div>
            @endif
        </div>
        @if ($variant === 'image' && $data['imageUrl'])
            <img src="{{ $data['imageUrl'] }}" alt="{{ $data['image_alt'] ?? '' }}" class="h-auto w-full rounded-card object-cover" loading="eager" fetchpriority="high" decoding="async">
        @elseif ($variant === 'ecosystem' && ! $center)
            <x-blocks.partials.ecosystem-visual class="hidden lg:block" />
        @endif
    </div>
</x-ui.section>
