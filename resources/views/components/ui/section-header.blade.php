{{-- Eyebrow + heading + intro pattern used at the top of most sections. --}}
@props(['eyebrow' => null, 'title' => null, 'intro' => null, 'level' => 'h2', 'align' => 'left', 'size' => 'h2'])
<div {{ $attributes->merge(['class' => 'max-w-3xl '.($align === 'center' ? 'mx-auto text-center' : '')]) }}>
    @if ($eyebrow)
        <x-ui.eyebrow>{{ $eyebrow }}</x-ui.eyebrow>
    @endif
    @if ($title)
        <{{ $level }} class="text-{{ $size }} mt-3 text-fg">{{ $title }}</{{ $level }}>
    @endif
    @if ($intro)
        <p class="mt-4 text-body-lg text-fg-secondary">{{ $intro }}</p>
    @endif
    @if (trim($slot))
        <div class="mt-6 flex flex-wrap gap-3 {{ $align === 'center' ? 'justify-center' : '' }}">{{ $slot }}</div>
    @endif
</div>
