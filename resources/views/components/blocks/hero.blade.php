@props(['data', 'host' => null, 'preview' => false])
@php
    $center = ($data['alignment'] ?? 'left') === 'center';
    $variant = $data['variant'] ?? 'ecosystem';
    // Highlight the closing words of the headline in the brand gradient.
    $words = preg_split('/\s+/', trim((string) $data['headline'])) ?: [];
    $highlightCount = count($words) >= 6 ? 2 : (count($words) >= 3 ? 1 : 0);
    $lead = implode(' ', array_slice($words, 0, count($words) - $highlightCount));
    $highlight = implode(' ', array_slice($words, count($words) - $highlightCount));
    $carousel = $variant === 'carousel' && ($data['slides'] ?? []) !== [];
@endphp
<x-ui.section :theme="$data['theme'] ?? 'dark'" pattern="mesh" spacing="sm" :id="$data['anchor'] ?? null" as="div" class="flex flex-col justify-center lg:min-h-[min(calc(100svh-4.5rem),50rem)]">
    <div @class([
        'grid grid-cols-1 gap-10 lg:items-center',
        'lg:grid-cols-2' => $variant === 'image' && $data['imageUrl'],
        'lg:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)]' => $variant === 'ecosystem' && ! $center,
        'lg:grid-cols-[3fr_2fr]' => $carousel,
        'mx-auto text-center' => $center && ! $carousel,
    ])>
        <div @class(['max-w-3xl py-6 lg:py-8', 'mx-auto' => $center])>
            @if (filled($data['eyebrow'] ?? null))
                <x-ui.eyebrow>{{ $data['eyebrow'] }}</x-ui.eyebrow>
            @endif
            <h1 class="mt-6 text-display">{{ $lead }}@if ($highlightCount > 0) <span class="text-gradient-brand">{{ $highlight }}</span>@endif</h1>
            @if (filled($data['subheading'] ?? null))
                <p class="mt-5 max-w-2xl text-body-lg text-fg-secondary {{ $center ? 'mx-auto' : '' }}">{{ $data['subheading'] }}</p>
            @endif
            @if ($data['primaryCta'] || $data['secondaryCta'])
                <div class="mt-8 flex flex-wrap gap-4 {{ $center ? 'justify-center' : '' }}">
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
            <div class="relative">
                <div class="absolute -inset-6 rounded-[2.5rem] bg-brand/25 blur-3xl" aria-hidden="true"></div>
                <img src="{{ $data['imageUrl'] }}" alt="{{ $data['image_alt'] ?? '' }}" class="relative h-auto w-full rounded-card object-cover shadow-lift" loading="eager" fetchpriority="high" decoding="async">
            </div>
        @elseif ($carousel)
            <x-blocks.partials.hero-carousel :slides="$data['slides']" :autoplay="(int) ($data['autoplay_seconds'] ?? 6)" :ratio="$data['slide_ratio'] ?? 'portrait'" />
        @elseif ($variant === 'ecosystem' && ! $center)
            <x-blocks.partials.ecosystem-visual class="hidden lg:flex lg:justify-end" />
        @endif
    </div>
</x-ui.section>
