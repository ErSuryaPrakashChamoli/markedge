{{-- Minimal head until the MetaResolver lands in Phase 6. Everything is escaped. --}}
@props(['title' => null, 'description' => null, 'canonical' => null, 'robots' => null])
@php
    $siteName = config('app.name');
    $fullTitle = filled($title) ? "{$title} | {$siteName}" : $siteName;
    $robots = $robots ?? (app()->isProduction() ? 'index, follow' : 'noindex, nofollow');
@endphp
<title>{{ $fullTitle }}</title>
@if (filled($description))
    <meta name="description" content="{{ $description }}">
@endif
<meta name="robots" content="{{ $robots }}">
<link rel="canonical" href="{{ $canonical ?? url()->current() }}">
<meta property="og:site_name" content="{{ $siteName }}">
<meta property="og:title" content="{{ $fullTitle }}">
@if (filled($description))
    <meta property="og:description" content="{{ $description }}">
@endif
<meta property="og:url" content="{{ $canonical ?? url()->current() }}">
<meta property="og:type" content="website">
<meta name="twitter:card" content="summary">
<link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
<meta name="theme-color" content="#16191d">
