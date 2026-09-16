{{-- Shared page hero for entity templates: breadcrumbs, eyebrow, H1, intro, optional image and CTA links. --}}
@props(['eyebrow' => null, 'title', 'intro' => null, 'breadcrumbs' => [], 'media' => null, 'cta' => null, 'entityName' => null, 'theme' => 'dark', 'badge' => null])
<x-ui.section :theme="$theme" pattern="grid" spacing="sm" {{ $attributes }}>
    @if ($breadcrumbs !== [])
        <x-layout.breadcrumbs :items="$breadcrumbs" class="mb-8" />
    @endif
    <div @class(['grid grid-cols-1 gap-10 lg:grid-cols-[3fr_2fr] lg:items-center' => $media !== null])>
        <div class="max-w-3xl py-4 lg:py-8">
            <div class="flex flex-wrap items-center gap-3">
                @if ($eyebrow)
                    <x-ui.eyebrow>{{ $eyebrow }}</x-ui.eyebrow>
                @endif
                @if ($badge)
                    <x-ui.badge tone="info">{{ $badge }}</x-ui.badge>
                @endif
            </div>
            <h1 class="mt-4 text-h1">{{ $title }}</h1>
            @if ($intro)
                <p class="mt-5 max-w-2xl text-body-lg text-fg-secondary">{{ $intro }}</p>
            @endif
            @if ($cta)
                <div class="mt-8 flex flex-wrap gap-3">
                    <x-cta.button :cta="$cta" :entity="$entityName" size="lg" />
                    <x-cta.button :cta="$cta" :entity="$entityName" size="lg" secondary />
                </div>
            @endif
            {{ $slot }}
        </div>
        @if ($media)
            <x-ui.picture :media="$media" conversion="hero" priority sizes="(min-width: 1024px) 40vw, 100vw" />
        @endif
    </div>
</x-ui.section>
