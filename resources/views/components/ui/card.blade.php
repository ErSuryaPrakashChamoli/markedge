{{-- Flat bordered surface. interactive adds hover affordance; href renders the whole card as a link. --}}
@props(['href' => null, 'interactive' => false, 'padding' => true, 'as' => null])
@php
    $tag = $as ?? ($href ? 'a' : 'div');
    $classes = 'group relative flex flex-col rounded-card border border-line bg-surface transition-colors duration-150 '.($padding ? 'card-p ' : '').(($interactive || $href) ? 'hover:border-brand focus-visible:border-brand' : '');
@endphp
<{{ $tag }} @if ($href) href="{{ $href }}" @endif {{ $attributes->merge(['class' => trim($classes)]) }}>
    {{ $slot }}
</{{ $tag }}>
