{{-- tone: neutral | brand | success | warning | danger | info. size: sm | md --}}
@props(['tone' => 'neutral', 'size' => 'md'])
@php
    $tones = [
        'neutral' => 'border-line bg-canvas-muted text-fg-secondary',
        'brand' => 'border-transparent bg-brand-soft text-brand',
        'success' => 'border-transparent bg-success/10 text-success',
        'warning' => 'border-transparent bg-warning/10 text-warning',
        'danger' => 'border-transparent bg-danger/10 text-danger',
        'info' => 'border-transparent bg-info/10 text-info',
    ];
    $sizes = ['sm' => 'px-2 py-0.5 text-[0.6875rem]', 'md' => 'px-2.5 py-1 text-meta'];
@endphp
<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-pill border font-semibold tracking-wide uppercase {$tones[$tone]} {$sizes[$size]}"]) }}>{{ $slot }}</span>
