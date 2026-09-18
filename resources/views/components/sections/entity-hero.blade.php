{{-- Shared page hero for entity templates: breadcrumbs, eyebrow, H1, intro, optional image and CTA links. --}}
@props(['eyebrow' => null, 'title', 'intro' => null, 'breadcrumbs' => [], 'media' => null, 'cta' => null, 'entityName' => null, 'theme' => 'dark', 'badge' => null])
{{-- Compact on purpose: the content below the hero (forms included) should start above the fold. --}}
<x-ui.section :theme="$theme" pattern="mesh" spacing="none" {{ $attributes->merge(['class' => 'py-5 lg:py-6']) }}>
    @if ($breadcrumbs !== [])
        <x-layout.breadcrumbs :items="$breadcrumbs" class="mb-3" />
    @endif
    <div @class([
        'grid grid-cols-1 gap-8 lg:grid-cols-[3fr_2fr] lg:items-center' => $media !== null,
        'lg:flex lg:items-end lg:justify-between lg:gap-10' => $media === null && $cta,
    ])>
        <div class="max-w-3xl">
            @if ($eyebrow || $badge)
                <div class="flex flex-wrap items-center gap-3">
                    @if ($eyebrow)
                        <x-ui.eyebrow>{{ $eyebrow }}</x-ui.eyebrow>
                    @endif
                    @if ($badge)
                        <x-ui.badge tone="info">{{ $badge }}</x-ui.badge>
                    @endif
                </div>
            @endif
            <h1 @class(['text-h1', 'mt-3' => $eyebrow || $badge])>{{ $title }}</h1>
            @if ($intro)
                <p class="mt-3 max-w-2xl text-body-lg text-fg-secondary">{{ $intro }}</p>
            @endif
            @if ($cta && $media)
                <div class="mt-5 flex flex-wrap gap-3">
                    <x-cta.button :cta="$cta" :entity="$entityName" />
                    <x-cta.button :cta="$cta" :entity="$entityName" secondary />
                </div>
            @endif
            {{ $slot }}
        </div>
        @if ($cta && ! $media)
            <div class="mt-5 flex flex-wrap gap-3 lg:mt-0 lg:shrink-0 lg:pb-1">
                <x-cta.button :cta="$cta" :entity="$entityName" />
                <x-cta.button :cta="$cta" :entity="$entityName" secondary />
            </div>
        @elseif ($media)
            <div class="relative">
                <div class="absolute -inset-4 rounded-[2rem] bg-brand/20 blur-3xl" aria-hidden="true"></div>
                <x-ui.picture :media="$media" conversion="hero" priority sizes="(min-width: 1024px) 40vw, 100vw" class="relative shadow-lift" />
            </div>
        @endif
    </div>
</x-ui.section>
