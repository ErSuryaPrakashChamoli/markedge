{{-- Shared page hero for entity templates: breadcrumbs, eyebrow, H1, intro, optional image and CTA links. --}}
@props(['eyebrow' => null, 'title', 'intro' => null, 'breadcrumbs' => [], 'media' => null, 'cta' => null, 'entityName' => null, 'theme' => 'dark', 'badge' => null])
<x-ui.section :theme="$theme" pattern="mesh" spacing="sm" {{ $attributes }}>
    @if ($breadcrumbs !== [])
        <x-layout.breadcrumbs :items="$breadcrumbs" class="mb-8" />
    @endif
    <div @class(['grid grid-cols-1 gap-12 lg:grid-cols-[3fr_2fr] lg:items-center' => $media !== null])>
        <div class="max-w-3xl py-6 lg:py-12">
            <div class="flex flex-wrap items-center gap-3">
                @if ($eyebrow)
                    <x-ui.eyebrow>{{ $eyebrow }}</x-ui.eyebrow>
                @endif
                @if ($badge)
                    <x-ui.badge tone="info">{{ $badge }}</x-ui.badge>
                @endif
            </div>
            <h1 class="mt-6 text-h1">{{ $title }}</h1>
            @if ($intro)
                <p class="mt-6 max-w-2xl text-body-lg text-fg-secondary">{{ $intro }}</p>
            @endif
            @if ($cta)
                <div class="mt-10 flex flex-wrap gap-3">
                    <x-cta.button :cta="$cta" :entity="$entityName" size="lg" />
                    <x-cta.button :cta="$cta" :entity="$entityName" size="lg" secondary />
                </div>
            @endif
            {{ $slot }}
        </div>
        @if ($media)
            <div class="relative">
                <div class="absolute -inset-4 rounded-[2rem] bg-brand/20 blur-3xl" aria-hidden="true"></div>
                <x-ui.picture :media="$media" conversion="hero" priority sizes="(min-width: 1024px) 40vw, 100vw" class="relative shadow-lift" />
            </div>
        @endif
    </div>
</x-ui.section>
