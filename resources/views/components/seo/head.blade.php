{{-- Head metadata from a resolved PageMeta (architecture §15.4). Every value is escaped; JSON-LD is emitted only when a graph exists. --}}
@props(['meta' => null])
@inject('metaResolver', 'App\Seo\MetaResolver')
@php
    /** @var \App\Seo\PageMeta $meta */
    $meta ??= $metaResolver->default();
    $siteName = config('app.name');
@endphp
<title>{{ $meta->title }}</title>
@if (filled($meta->description))
    <meta name="description" content="{{ $meta->description }}">
@endif
<meta name="robots" content="{{ $meta->robots }}">
@if ($meta->canonical)
    <link rel="canonical" href="{{ $meta->canonical }}">
@endif
<meta property="og:site_name" content="{{ $siteName }}">
<meta property="og:type" content="{{ $meta->ogType }}">
<meta property="og:title" content="{{ $meta->ogTitle ?? $meta->title }}">
@if (filled($meta->ogDescription))
    <meta property="og:description" content="{{ $meta->ogDescription }}">
@endif
@if ($meta->canonical)
    <meta property="og:url" content="{{ $meta->canonical }}">
@endif
@if ($meta->ogImage)
    <meta property="og:image" content="{{ $meta->ogImage }}">
@endif
@if ($meta->ogType === 'article' && $meta->publishedTime)
    <meta property="article:published_time" content="{{ $meta->publishedTime }}">
    @if ($meta->modifiedTime)
        <meta property="article:modified_time" content="{{ $meta->modifiedTime }}">
    @endif
@endif
<meta name="twitter:card" content="{{ ($meta->twitterImage ?? $meta->ogImage) ? 'summary_large_image' : 'summary' }}">
<meta name="twitter:title" content="{{ $meta->twitterTitle ?? $meta->title }}">
@if (filled($meta->twitterDescription))
    <meta name="twitter:description" content="{{ $meta->twitterDescription }}">
@endif
@if ($meta->twitterImage ?? $meta->ogImage)
    <meta name="twitter:image" content="{{ $meta->twitterImage ?? $meta->ogImage }}">
@endif
@if ($meta->schema !== [])
    <script type="application/ld+json">{!! \App\Seo\Schema\SchemaGraphBuilder::encode($meta->schema) !!}</script>
@endif
<link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
<meta name="theme-color" content="#16191d">
