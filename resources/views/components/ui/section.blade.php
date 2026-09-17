{{--
    Vertical rhythm + theme scope. theme: light | dark | neutral. spacing: default | sm | none.
    pattern: none | grid | dots | mesh. Children use semantic tokens so they adapt to the theme automatically.
--}}
@props(['theme' => 'light', 'spacing' => 'default', 'pattern' => 'none', 'container' => 'default', 'id' => null, 'as' => 'section', 'reveal' => false])
@php
    $padding = match ($spacing) { 'sm' => 'section-py-sm', 'none' => '', default => 'section-py' };
    $patternClass = match ($pattern) { 'grid' => 'bg-grid-pattern', 'dots' => 'bg-dots-pattern', 'mesh' => 'bg-mesh', default => '' };
@endphp
<{{ $as }}
    @if ($id) id="{{ $id }}" @endif
    data-theme="{{ $theme }}"
    @if ($reveal) data-reveal @endif
    {{ $attributes->merge(['class' => trim("relative overflow-hidden bg-canvas text-fg {$padding} {$patternClass}")]) }}
>
    @if ($pattern === 'mesh')
        <div class="orb -top-24 right-[-8%] size-[28rem] bg-brand/40 theme-dark:bg-brand/50" aria-hidden="true"></div>
        <div class="orb bottom-[-30%] left-[-6%] size-[24rem] bg-[color:var(--color-sky-500)]/25 [animation-delay:-9s]" aria-hidden="true"></div>
    @endif
    @if ($container === 'none')
        {{ $slot }}
    @else
        <x-ui.container :size="$container" class="relative">{{ $slot }}</x-ui.container>
    @endif
</{{ $as }}>
