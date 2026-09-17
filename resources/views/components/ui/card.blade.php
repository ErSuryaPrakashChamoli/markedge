{{-- Elevated surface. interactive adds the hover lift and brand accent; href renders the whole card as a link. --}}
@props(['href' => null, 'interactive' => false, 'padding' => true, 'as' => null])
@php
    $tag = $as ?? ($href ? 'a' : 'div');
    $classes = 'group relative flex flex-col overflow-hidden rounded-card border border-line bg-surface shadow-card '.($padding ? 'card-p ' : '').(($interactive || $href) ? 'card-hover focus-visible:border-brand' : '');
@endphp
<{{ $tag }} @if ($href) href="{{ $href }}" @endif {{ $attributes->merge(['class' => trim($classes)]) }}>
    {{ $slot }}
</{{ $tag }}>
