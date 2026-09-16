@props(['href'])
<a href="{{ $href }}" {{ $attributes->merge(['class' => 'group inline-flex items-center gap-1.5 text-button text-fg hover:text-brand']) }}>
    {{ $slot }}
    <x-ui.icon name="heroicon-m-arrow-right" class="size-4 transition-transform group-hover:translate-x-0.5" />
</a>
