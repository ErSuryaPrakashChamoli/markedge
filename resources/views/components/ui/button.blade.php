{{--
    variant: primary | secondary | outline | ghost | link
    size: sm | md | lg
    Renders <a> when href is given, otherwise <button>.
--}}
@props(['variant' => 'primary', 'size' => 'md', 'href' => null, 'type' => 'button', 'icon' => null, 'iconPosition' => 'right', 'disabled' => false])
@php
    $base = 'group/btn inline-flex items-center justify-center gap-2 rounded-control text-button transition-all duration-300 ease-[cubic-bezier(0.22,1,0.36,1)] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand disabled:cursor-not-allowed disabled:opacity-50';
    $variants = [
        'primary' => 'btn-shine bg-brand-gradient-btn text-white shadow-glow hover:-translate-y-0.5 hover:shadow-glow-lg active:translate-y-0',
        'secondary' => 'bg-brand-secondary text-fg-inverse shadow-card hover:-translate-y-0.5 hover:shadow-lift active:translate-y-0',
        'outline' => 'border border-line-strong/70 bg-surface/60 text-fg backdrop-blur hover:-translate-y-0.5 hover:border-brand hover:text-brand hover:shadow-card active:translate-y-0',
        'ghost' => 'bg-transparent text-fg-secondary hover:bg-canvas-muted hover:text-fg',
        'link' => 'bg-transparent p-0 text-fg underline decoration-brand decoration-2 underline-offset-4 hover:text-brand',
    ];
    $sizes = [
        'sm' => 'h-9 px-4 text-[0.875rem]',
        'md' => 'h-11 px-6',
        'lg' => 'h-12 px-7 text-base',
    ];
    $classes = trim("{$base} {$variants[$variant]} ".($variant === 'link' ? '' : $sizes[$size]));
    $iconClass = 'size-4 transition-transform duration-300 group-hover/btn:translate-x-0.5';
@endphp
@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }} @if ($disabled) aria-disabled="true" tabindex="-1" @endif>
        @if ($icon && $iconPosition === 'left')<x-ui.icon :name="$icon" class="size-4" />@endif
        {{ $slot }}
        @if ($icon && $iconPosition === 'right')<x-ui.icon :name="$icon" :class="$iconClass" />@endif
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }} @disabled($disabled)>
        @if ($icon && $iconPosition === 'left')<x-ui.icon :name="$icon" class="size-4" />@endif
        {{ $slot }}
        @if ($icon && $iconPosition === 'right')<x-ui.icon :name="$icon" :class="$iconClass" />@endif
    </button>
@endif
