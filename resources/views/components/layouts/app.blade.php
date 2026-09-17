@props([
    'meta' => null,
    'title' => null,
    'description' => null,
    'robots' => null,
    'bodyClass' => '',
    'minimalHeader' => false,
    'minimalFooter' => false,
    'showStickyCta' => true,
    'preview' => false,
])
@inject('metaResolver', 'App\Seo\MetaResolver')
@php
    // Pages without a resolved PageMeta (styleguide, errors) pass simple strings instead.
    $meta ??= $metaResolver->forListing($title ?? config('app.name'), $description, request()->path())->with(array_filter([
        'robots' => $robots,
        'canonical' => $robots && str_contains($robots, 'noindex') ? null : request()->url(),
    ], fn ($value) => $value !== null));

    // Scroll-reveal signature per page type (resources/css/app.css "Motion").
    $routeName = (string) (request()->route()?->getName() ?? '');
    $motion = match (true) {
        $routeName === 'home' => 'rise',
        str_starts_with($routeName, 'services.') => 'slide',
        str_starts_with($routeName, 'products.') => 'scale',
        str_starts_with($routeName, 'solutions.') => 'curtain',
        str_starts_with($routeName, 'industries.') => 'sweep',
        str_starts_with($routeName, 'case-studies.') => 'focus',
        str_starts_with($routeName, 'insights.') => 'lift',
        str_starts_with($routeName, 'landing.') => 'landing',
        default => 'fade',
    };
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <x-seo.head :meta="$meta" />
    {{ $head ?? '' }}
    @stack('head')
    @fonts
    @php
        // Livewire (and its bundled Alpine) is only shipped when the page actually rendered a
        // Livewire component; every other page loads the lean Alpine entry (Phase 10 §5).
        $needsLivewire = str_contains((string) $slot, 'wire:snapshot');
    @endphp
    <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">document.documentElement.classList.add('js-motion');</script>
    @vite(['resources/css/app.css', $needsLivewire ? 'resources/js/app.js' : 'resources/js/lean.js'])
    @if ($needsLivewire)
        @livewireStyles
    @endif
</head>
<body {{ $attributes->merge(['class' => 'flex min-h-full flex-col bg-canvas text-fg '.$bodyClass]) }} data-motion="{{ $motion }}" @if ($preview) data-preview @endif>
    <x-layout.skip-link />
    <div class="scroll-progress" aria-hidden="true"></div>
    @if ($preview)
        <x-layout.preview-banner />
    @else
        <x-layout.announcement-bar />
    @endif
    <x-layout.header :minimal="$minimalHeader" />

    <main id="main" class="flex-1">
        {{ $slot }}
    </main>

    <x-layout.footer :minimal="$minimalFooter" />
    @if ($showStickyCta && ! $minimalHeader && ! $preview)
        <x-layout.sticky-mobile-cta />
    @endif

    @if ($needsLivewire)
        @livewireScriptConfig
    @endif
</body>
</html>
