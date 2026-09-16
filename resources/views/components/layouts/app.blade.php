@props([
    'title' => null,
    'description' => null,
    'canonical' => null,
    'robots' => null,
    'bodyClass' => '',
    'minimalHeader' => false,
    'minimalFooter' => false,
    'showStickyCta' => true,
])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <x-seo.head :title="$title" :description="$description" :canonical="$canonical" :robots="$robots" />
    {{ $head ?? '' }}
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body {{ $attributes->merge(['class' => 'flex min-h-full flex-col bg-canvas text-fg '.$bodyClass]) }}>
    <x-layout.skip-link />
    <x-layout.announcement-bar />
    <x-layout.header :minimal="$minimalHeader" />

    <main id="main" class="flex-1">
        {{ $slot }}
    </main>

    <x-layout.footer :minimal="$minimalFooter" />
    @if ($showStickyCta && ! $minimalHeader)
        <x-layout.sticky-mobile-cta />
    @endif

    @livewireScriptConfig
</body>
</html>
