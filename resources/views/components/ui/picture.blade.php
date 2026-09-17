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
    <div {{ $attributes->merge(['class' => "relative w-full overflow-hidden rounded-card border border-line bg-canvas-muted bg-mesh-soft shadow-card {$ratio}"]) }} role="img" aria-label="{{ $alt ?: 'Illustration placeholder' }}">
        <div class="absolute inset-[12%] rounded-[1.25rem] border border-line bg-surface/90 p-5 shadow-lift backdrop-blur" aria-hidden="true">
            <div class="flex items-center gap-2">
                <span class="size-2.5 rounded-full bg-brand"></span><span class="size-2.5 rounded-full bg-line-strong/60"></span><span class="size-2.5 rounded-full bg-line-strong/60"></span>
                <span class="ml-auto h-2 w-1/4 rounded-pill bg-brand-soft"></span>
            </div>
            <div class="mt-5 grid grid-cols-3 gap-3">
                <div class="col-span-2 space-y-2.5">
                    <span class="block h-3 w-3/4 rounded-pill bg-fg/15"></span>
                    <span class="block h-2.5 w-full rounded-pill bg-fg/8"></span>
                    <span class="block h-2.5 w-5/6 rounded-pill bg-fg/8"></span>
                    <span class="mt-4 inline-block h-8 w-28 rounded-control bg-brand-gradient-btn shadow-glow"></span>
                </div>
                <div class="rounded-control bg-brand-soft p-3">
                    <span class="block h-2 w-1/2 rounded-pill bg-brand/40"></span>
                    <span class="mt-3 block h-6 w-3/4 rounded-pill bg-brand/70"></span>
                    <span class="mt-2 block h-2 w-2/3 rounded-pill bg-brand/30"></span>
                </div>
            </div>
            <div class="mt-4 flex items-end gap-1.5">
                @foreach ([40, 65, 50, 80, 60, 95, 70] as $h)
                    <span class="block flex-1 rounded-t-md bg-gradient-to-t from-brand/70 to-brand/25" style="height: {{ $h * 0.4 }}px"></span>
                @endforeach
            </div>
        </div>
        @if ($placeholderLabel)
            <span class="absolute bottom-3 left-3 rounded-control bg-surface/90 px-2 py-1 text-caption text-fg-muted">{{ $placeholderLabel }}</span>
        @endif
    </div>
@endif
