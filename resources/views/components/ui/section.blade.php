{{--
    Vertical rhythm + theme scope. theme: light | dark | neutral. spacing: default | sm | none.
    pattern: none | grid | dots. Children use semantic tokens so they adapt to the theme automatically.
--}}
@props(['theme' => 'light', 'spacing' => 'default', 'pattern' => 'none', 'container' => 'default', 'id' => null, 'as' => 'section', 'reveal' => false])
@php
    $padding = match ($spacing) { 'sm' => 'section-py-sm', 'none' => '', default => 'section-py' };
    $patternClass = match ($pattern) { 'grid' => 'bg-grid-pattern', 'dots' => 'bg-dots-pattern', default => '' };
@endphp
<{{ $as }}
    @if ($id) id="{{ $id }}" @endif
    data-theme="{{ $theme }}"
    @if ($reveal) data-reveal @endif
    {{ $attributes->merge(['class' => trim("relative bg-canvas text-fg {$padding} {$patternClass}")]) }}
>
    @if ($container === 'none')
        {{ $slot }}
    @else
        <x-ui.container :size="$container">{{ $slot }}</x-ui.container>
    @endif
</{{ $as }}>
