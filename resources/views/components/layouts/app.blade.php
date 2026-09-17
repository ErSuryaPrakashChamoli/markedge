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
    @vite(['resources/css/app.css', $needsLivewire ? 'resources/js/app.js' : 'resources/js/lean.js'])
    @if ($needsLivewire)
        @livewireStyles
    @endif
</head>
<body {{ $attributes->merge(['class' => 'flex min-h-full flex-col bg-canvas text-fg '.$bodyClass]) }} @if ($preview) data-preview @endif>
    <x-layout.skip-link />
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
