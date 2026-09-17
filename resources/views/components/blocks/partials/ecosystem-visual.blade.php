{{-- Decorative BUILD / OPERATE / GROW diagram drawn with the design tokens; no images, no claims. --}}
<div {{ $attributes->merge(['class' => 'relative']) }} aria-hidden="true">
    <svg viewBox="0 0 480 360" class="mx-auto w-full max-w-sm xl:max-w-md" role="presentation" fill="none">
        <defs>
            <linearGradient id="mk-eco-line" x1="0" x2="1" y1="0" y2="1">
                <stop offset="0" stop-color="var(--mk-line-strong)" />
                <stop offset="1" stop-color="var(--mk-brand)" />
            </linearGradient>
        </defs>
        <g stroke="url(#mk-eco-line)" stroke-width="1.5">
            <path d="M240 60 L120 210 M240 60 L360 210 M120 210 L360 210" />
            <path d="M240 60 L240 300" stroke-dasharray="4 6" opacity="0.5" />
        </g>
        @foreach ([[240, 60, 'BUILD'], [120, 210, 'OPERATE'], [360, 210, 'GROW'], [240, 300, 'PRODUCTS']] as [$x, $y, $label])
            <g transform="translate({{ $x }} {{ $y }})">
                <rect x="-56" y="-22" width="112" height="44" rx="4" fill="var(--mk-surface-elevated)" stroke="var(--mk-line-strong)" />
                <text text-anchor="middle" dominant-baseline="middle" font-size="12" font-weight="600" letter-spacing="0.12em" fill="{{ $label === 'PRODUCTS' ? 'var(--mk-brand)' : 'var(--mk-fg)' }}">{{ $label }}</text>
            </g>
        @endforeach
    </svg>
</div>
