{{-- Page width primitive. size: default | narrow | prose | wide | full --}}
@props(['size' => 'default', 'as' => 'div'])
@php
    $width = match ($size) {
        'narrow' => 'max-w-4xl',
        'prose' => 'max-w-3xl',
        'wide' => 'max-w-[90rem]',
        'full' => 'max-w-none',
        default => 'max-w-7xl',
    };
@endphp
<{{ $as }} {{ $attributes->merge(['class' => "mx-auto w-full gutter-px {$width}"]) }}>
    {{ $slot }}
</{{ $as }}>
