{{-- Responsive grid. cols: 2 | 3 | 4. Always 1 column on mobile, 2 on tablet. --}}
@props(['cols' => 3, 'as' => 'div'])
@php
    $columns = match ((int) $cols) {
        2 => 'md:grid-cols-2',
        4 => 'md:grid-cols-2 xl:grid-cols-4',
        default => 'md:grid-cols-2 lg:grid-cols-3',
    };
@endphp
<{{ $as }} {{ $attributes->merge(['class' => "grid grid-cols-1 grid-gap {$columns}"]) }}>
    {{ $slot }}
</{{ $as }}>
