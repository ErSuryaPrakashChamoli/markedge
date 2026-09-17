{{--
    Responsive image from a Spatie Media item (Phase 10 §7). Emits <picture> with AVIF and WebP
    sources when those conversions exist, an <img> fallback, intrinsic width/height to prevent
    layout shift, lazy loading below the fold and eager + high priority for the LCP image.
    Falls back to a neutral placeholder so layouts hold their shape before assets exist.
    conversion: thumb | card | hero
--}}
@props(['media' => null, 'conversion' => 'card', 'alt' => null, 'sizes' => '(min-width: 1024px) 50vw, 100vw', 'priority' => false, 'ratio' => 'aspect-[16/10]', 'placeholderLabel' => null])
@php
    $widths = \App\Media\Conversions::WIDTHS;
    $src = null;
    $webp = [];
    $avif = [];
    $width = null;
    $height = null;

    if ($media) {
        $src = $media->hasGeneratedConversion($conversion) ? $media->getUrl($conversion) : $media->getUrl();
        $alt ??= $media->getCustomProperty('alt', '');
        $originalWidth = (int) $media->getCustomProperty('width', 0);
        $originalHeight = (int) $media->getCustomProperty('height', 0);

        foreach ($widths as $name => $w) {
            if ($media->hasGeneratedConversion($name)) {
                $webp[] = $media->getUrl($name)." {$w}w";
            }

            if ($media->hasGeneratedConversion("{$name}_avif")) {
                $avif[] = $media->getUrl("{$name}_avif")." {$w}w";
            }
        }

        if ($originalWidth > 0 && $originalHeight > 0) {
            $target = $media->hasGeneratedConversion($conversion) ? min($widths[$conversion] ?? $originalWidth, $originalWidth) : $originalWidth;
            $width = $target;
            $height = (int) round($originalHeight * ($target / $originalWidth));
        }
    }
@endphp
@if ($src)
    <picture>
        @if ($avif)
            <source type="image/avif" srcset="{{ implode(', ', $avif) }}" sizes="{{ $sizes }}">
        @endif
        @if ($webp)
            <source type="image/webp" srcset="{{ implode(', ', $webp) }}" sizes="{{ $sizes }}">
        @endif
        <img
            src="{{ $src }}"
            alt="{{ $alt }}"
            @if ($width) width="{{ $width }}" @endif
            @if ($height) height="{{ $height }}" @endif
            loading="{{ $priority ? 'eager' : 'lazy' }}"
            @if ($priority) fetchpriority="high" @endif
            decoding="async"
            {{ $attributes->merge(['class' => 'h-auto w-full rounded-card object-cover']) }}
        >
    </picture>
@else
    <div {{ $attributes->merge(['class' => "flex w-full items-center justify-center rounded-card border border-line bg-canvas-muted bg-grid-pattern text-caption text-fg-muted {$ratio}"]) }} role="img" aria-label="{{ $alt ?: 'Image placeholder' }}">
        @if ($placeholderLabel)
            <span class="rounded-control bg-surface px-2 py-1">{{ $placeholderLabel }}</span>
        @endif
    </div>
@endif
