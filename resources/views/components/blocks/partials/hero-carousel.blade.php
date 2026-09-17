{{-- Admin-managed hero slides (Phase hero block, variant "carousel"). Placement, size and colour are presets, never free CSS. --}}
@props(['slides', 'autoplay' => 6, 'ratio' => 'portrait'])
@php
    $ratioClass = match ($ratio) { 'square' => 'aspect-square', 'landscape' => 'aspect-[4/3]', default => 'aspect-[4/5]' };
    $positions = [
        'top-left' => 'items-start justify-start text-left', 'top-center' => 'items-start justify-center text-center', 'top-right' => 'items-start justify-end text-right',
        'center-left' => 'items-center justify-start text-left', 'center' => 'items-center justify-center text-center', 'center-right' => 'items-center justify-end text-right',
        'bottom-left' => 'items-end justify-start text-left', 'bottom-center' => 'items-end justify-center text-center', 'bottom-right' => 'items-end justify-end text-right',
    ];
    $colours = ['white' => 'text-white', 'dark' => 'text-[color:var(--color-charcoal-900)]', 'brand' => 'text-brand'];
    $sizes = ['sm' => ['text-h4', 'text-caption'], 'md' => ['text-h3', 'text-body-sm'], 'lg' => ['text-h2', 'text-body']];
    $buttons = ['primary' => 'primary', 'light' => 'secondary', 'outline' => 'outline'];
@endphp
<div
    x-data="carousel({ count: {{ count($slides) }}, autoplay: {{ (int) $autoplay }} })"
    @mouseenter="pause()" @mouseleave="resume()" @keydown.arrow-left.prevent="prev()" @keydown.arrow-right.prevent="next()"
    class="hero-carousel relative"
    role="region" aria-roledescription="carousel" aria-label="Highlights" tabindex="0"
>
    <div class="absolute -inset-5 rounded-[2.25rem] bg-brand/25 blur-3xl" aria-hidden="true"></div>
    <div class="relative overflow-hidden rounded-card shadow-lift {{ $ratioClass }}">
        @foreach ($slides as $index => $slide)
            @php
                $size = $sizes[$slide['text_size'] ?? 'md'] ?? $sizes['md'];
                $colour = $colours[$slide['text_color'] ?? 'white'] ?? $colours['white'];
                $isLight = ($slide['text_color'] ?? 'white') !== 'dark';
            @endphp
            <div
                x-show="index === {{ $index }}"
                x-transition:enter="transition duration-700 ease-out" x-transition:enter-start="opacity-0 scale-105" x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition duration-500 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                @if ($index !== 0) x-cloak @endif
                class="absolute inset-0"
                role="group" aria-roledescription="slide" aria-label="{{ $index + 1 }} of {{ count($slides) }}"
            >
                <img src="{{ $slide['imageUrl'] }}" alt="{{ $slide['image_alt'] ?? '' }}" class="hero-slide-img h-full w-full object-cover" @if ($index === 0) loading="eager" fetchpriority="high" @else loading="lazy" @endif decoding="async">
                @if (filled($slide['heading'] ?? null) || filled($slide['subheading'] ?? null) || (filled($slide['button_label'] ?? null) && filled($slide['button_url'] ?? null)))
                    <div class="absolute inset-0 {{ $isLight ? 'bg-gradient-to-t from-[color:rgb(7_13_24_/_0.75)] via-[color:rgb(7_13_24_/_0.25)] to-transparent' : 'bg-gradient-to-t from-white/70 via-white/20 to-transparent' }}" aria-hidden="true"></div>
                    <div class="absolute inset-0 flex p-6 md:p-8 {{ $positions[$slide['text_position'] ?? 'bottom-left'] ?? $positions['bottom-left'] }}">
                        <div class="max-w-[85%] {{ $colour }}">
                            @if (filled($slide['heading'] ?? null))
                                <p class="{{ $size[0] }} drop-shadow-sm">{{ $slide['heading'] }}</p>
                            @endif
                            @if (filled($slide['subheading'] ?? null))
                                <p class="mt-2 {{ $size[1] }} opacity-90">{{ $slide['subheading'] }}</p>
                            @endif
                            @if (filled($slide['button_label'] ?? null) && filled($slide['button_url'] ?? null))
                                <div class="mt-4">
                                    <x-ui.button :href="$slide['button_url']" :variant="$buttons[$slide['button_style'] ?? 'primary'] ?? 'primary'" size="sm" icon="heroicon-m-arrow-right" :target="$slide['external'] ? '_blank' : null" :rel="$slide['external'] ? 'noopener' : null">{{ $slide['button_label'] }}</x-ui.button>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        @endforeach

        @if (count($slides) > 1)
            <button type="button" @click="prev()" class="absolute top-1/2 left-3 z-10 inline-flex size-10 -translate-y-1/2 items-center justify-center rounded-full bg-white/85 text-[color:var(--color-charcoal-900)] shadow-card backdrop-blur transition hover:bg-white hover:scale-105" aria-label="Previous slide">
                <x-ui.icon name="heroicon-m-chevron-left" class="size-5" />
            </button>
            <button type="button" @click="next()" class="absolute top-1/2 right-3 z-10 inline-flex size-10 -translate-y-1/2 items-center justify-center rounded-full bg-white/85 text-[color:var(--color-charcoal-900)] shadow-card backdrop-blur transition hover:bg-white hover:scale-105" aria-label="Next slide">
                <x-ui.icon name="heroicon-m-chevron-right" class="size-5" />
            </button>
            <div class="absolute bottom-3 left-1/2 z-10 flex -translate-x-1/2 gap-1.5" role="tablist" aria-label="Choose slide">
                @foreach ($slides as $index => $slide)
                    <button type="button" @click="go({{ $index }})" :aria-selected="index === {{ $index }}" :class="index === {{ $index }} ? 'w-6 bg-brand' : 'w-2 bg-white/70 hover:bg-white'" class="h-2 rounded-pill transition-all duration-300" role="tab" aria-label="Slide {{ $index + 1 }}"></button>
                @endforeach
            </div>
            @if ($autoplay > 0)
                <div class="absolute inset-x-0 top-0 z-10 h-1 bg-white/20" aria-hidden="true">
                    <div class="h-full bg-brand transition-[width] ease-linear" :style="`width: ${progress}%; transition-duration: ${playing ? 100 : 0}ms`"></div>
                </div>
            @endif
        @endif
    </div>
</div>
