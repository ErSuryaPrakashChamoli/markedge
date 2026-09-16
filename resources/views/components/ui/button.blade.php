{{--
    variant: primary | secondary | outline | ghost | link
    size: sm | md | lg
    Renders <a> when href is given, otherwise <button>.
--}}
@props(['variant' => 'primary', 'size' => 'md', 'href' => null, 'type' => 'button', 'icon' => null, 'iconPosition' => 'right', 'disabled' => false])
@php
    $base = 'inline-flex items-center justify-center gap-2 rounded-control text-button transition-colors duration-150 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand disabled:cursor-not-allowed disabled:opacity-50';
    $variants = [
        'primary' => 'bg-brand text-white hover:bg-brand-hover',
        'secondary' => 'bg-brand-secondary text-fg-inverse hover:opacity-90',
        'outline' => 'border border-line-strong bg-transparent text-fg hover:border-fg hover:bg-canvas-muted',
        'ghost' => 'bg-transparent text-fg-secondary hover:bg-canvas-muted hover:text-fg',
        'link' => 'bg-transparent p-0 text-fg underline decoration-brand decoration-2 underline-offset-4 hover:text-brand',
    ];
    $sizes = [
        'sm' => 'h-9 px-3.5 text-[0.875rem]',
        'md' => 'h-11 px-5',
        'lg' => 'h-12 px-6 text-base',
    ];
    $classes = trim("{$base} {$variants[$variant]} ".($variant === 'link' ? '' : $sizes[$size]));
    $iconEl = $icon ? '<x-ui.icon name="'.$icon.'" class="size-4" />' : null;
@endphp
@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }} @if ($disabled) aria-disabled="true" tabindex="-1" @endif>
        @if ($icon && $iconPosition === 'left')<x-ui.icon :name="$icon" class="size-4" />@endif
        {{ $slot }}
        @if ($icon && $iconPosition === 'right')<x-ui.icon :name="$icon" class="size-4" />@endif
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }} @disabled($disabled)>
        @if ($icon && $iconPosition === 'left')<x-ui.icon :name="$icon" class="size-4" />@endif
        {{ $slot }}
        @if ($icon && $iconPosition === 'right')<x-ui.icon :name="$icon" class="size-4" />@endif
    </button>
@endif
