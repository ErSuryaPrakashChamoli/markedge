{{-- Dependency-free shell for error pages that must render without the database. --}}
@props(['title' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' | ' : '' }}{{ config('app.name') }}</title>
    <meta name="robots" content="noindex, nofollow">
    @fonts
    @vite(['resources/css/app.css'])
</head>
<body class="flex min-h-full flex-col bg-canvas text-fg">
    <main class="flex flex-1 items-center">
        {{ $slot }}
    </main>
</body>
</html>
