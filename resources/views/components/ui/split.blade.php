{{-- Two-column layout. ratio: equal | wide-left | wide-right. Stacks on mobile. --}}
@props(['ratio' => 'equal', 'align' => 'center', 'reverseOnMobile' => false])
@php
    $columns = match ($ratio) {
        'wide-left' => 'lg:grid-cols-[3fr_2fr]',
        'wide-right' => 'lg:grid-cols-[2fr_3fr]',
        default => 'lg:grid-cols-2',
    };
    $items = match ($align) { 'start' => 'lg:items-start', 'end' => 'lg:items-end', default => 'lg:items-center' };
@endphp
<div {{ $attributes->merge(['class' => "grid grid-cols-1 gap-10 lg:gap-16 {$columns} {$items}"]) }}>
    <div @class(['order-2 lg:order-1' => $reverseOnMobile])>{{ $left }}</div>
    <div @class(['order-1 lg:order-2' => $reverseOnMobile])>{{ $right }}</div>
</div>
