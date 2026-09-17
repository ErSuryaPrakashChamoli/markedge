<p {{ $attributes->merge(['class' => 'inline-flex items-center gap-2 rounded-pill border border-brand/25 bg-brand-soft px-3 py-1.5 text-eyebrow text-brand']) }}>
    <span class="relative flex size-2" aria-hidden="true">
        <span class="absolute inline-flex size-full animate-ping rounded-full bg-brand opacity-60"></span>
        <span class="relative inline-flex size-2 rounded-full bg-brand"></span>
    </span>
    {{ $slot }}
</p>
