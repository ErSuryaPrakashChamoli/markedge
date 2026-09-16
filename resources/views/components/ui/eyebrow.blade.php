<p {{ $attributes->merge(['class' => 'inline-flex items-center gap-2 text-eyebrow text-brand']) }}>
    <span class="h-px w-6 bg-brand" aria-hidden="true"></span>{{ $slot }}
</p>
