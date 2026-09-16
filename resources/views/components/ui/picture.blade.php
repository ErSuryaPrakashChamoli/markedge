{{--
    Responsive image from a Spatie Media item. Falls back to a neutral placeholder so layouts
    hold their shape before assets exist. conversion: thumb | card | hero
--}}
@props(['media' => null, 'conversion' => 'card', 'alt' => null, 'sizes' => '(min-width: 1024px) 50vw, 100vw', 'priority' => false, 'ratio' => 'aspect-[16/10]', 'placeholderLabel' => null])
@php
    $widths = ['thumb' => 400, 'card' => 800, 'hero' => 1600];
    $src = null;
    $srcset = [];

    if ($media) {
        $src = $media->hasGeneratedConversion($conversion) ? $media->getUrl($conversion) : $media->getUrl();

        foreach ($widths as $name => $width) {
            if ($media->hasGeneratedConversion($name)) {
                $srcset[] = $media->getUrl($name)." {$width}w";
            }
        }

        $alt ??= $media->getCustomProperty('alt', '');
    }
@endphp
@if ($src)
    <img
        src="{{ $src }}"
        @if ($srcset) srcset="{{ implode(', ', $srcset) }}" sizes="{{ $sizes }}" @endif
        alt="{{ $alt }}"
        @if ($media->getCustomProperty('width')) width="{{ $media->getCustomProperty('width') }}" @endif
        @if ($media->getCustomProperty('height')) height="{{ $media->getCustomProperty('height') }}" @endif
        loading="{{ $priority ? 'eager' : 'lazy' }}"
        @if ($priority) fetchpriority="high" @endif
        decoding="async"
        {{ $attributes->merge(['class' => 'h-auto w-full rounded-card object-cover']) }}
    >
@else
    <div {{ $attributes->merge(['class' => "flex w-full items-center justify-center rounded-card border border-line bg-canvas-muted bg-grid-pattern text-caption text-fg-muted {$ratio}"]) }} role="img" aria-label="{{ $alt ?: 'Image placeholder' }}">
        @if ($placeholderLabel)
            <span class="rounded-control bg-surface px-2 py-1">{{ $placeholderLabel }}</span>
        @endif
    </div>
@endif
