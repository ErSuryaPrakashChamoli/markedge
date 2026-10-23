{{--
    Social video card. When the platform has an embed player the cover opens it in a dialog; the iframe is only
    created on play, so no third-party code loads until a visitor asks for it. Other links open the post.
--}}
@props(['video'])
@php
    $platform = $video->platform;
    $embed = $video->embedUrl();
    $cover = $video->relationLoaded('media') ? $video->getFirstMedia('featured') : null;
    $label = $platform?->getLabel() ?? 'Video';
    $platformKey = $platform?->value ?? 'other';
@endphp
<article {{ $attributes->merge(['class' => 'group relative flex flex-col overflow-hidden rounded-card border border-line bg-surface shadow-card card-hover']) }} @if ($embed) x-data="{ open: false }" x-effect="document.documentElement.classList.toggle('overflow-hidden', open)" @keydown.escape.window="open = false" @endif>
    @if ($embed)
        <button type="button" class="relative block aspect-[4/5] w-full overflow-hidden bg-canvas-dark text-left" @click="open = true" aria-haspopup="dialog" aria-label="Play {{ $video->title }}">
    @else
        <a href="{{ $video->url }}" target="_blank" rel="noopener" class="relative block aspect-[4/5] w-full overflow-hidden bg-canvas-dark" aria-label="Watch {{ $video->title }} on {{ $label }} (opens in a new tab)">
    @endif
        @if ($cover)
            <img src="{{ $cover->hasGeneratedConversion('card') ? $cover->getUrl('card') : $cover->getUrl() }}" alt="" class="absolute inset-0 size-full object-cover transition-transform duration-500 group-hover:scale-105" loading="lazy" decoding="async">
        @else
            <span data-theme="dark" class="absolute inset-0 flex items-center justify-center bg-mesh text-fg/25" aria-hidden="true">
                <x-ui.social-icon :platform="$platformKey" class="size-20" />
            </span>
        @endif
        <span class="absolute inset-0 bg-linear-to-t from-black/70 via-black/10 to-transparent" aria-hidden="true"></span>
        <span class="absolute top-3 left-3 inline-flex items-center gap-1.5 rounded-pill bg-black/55 px-2.5 py-1 text-meta font-semibold text-white backdrop-blur" aria-hidden="true">
            <x-ui.social-icon :platform="$platformKey" class="size-3.5" />
            {{ $label }}
        </span>
        <span class="absolute inset-0 flex items-center justify-center" aria-hidden="true">
            <span class="flex size-14 items-center justify-center rounded-pill bg-white/90 text-brand shadow-lift transition-transform duration-300 group-hover:scale-110">
                <x-ui.icon :name="$embed ? 'heroicon-s-play' : 'heroicon-m-arrow-top-right-on-square'" class="size-6" />
            </span>
        </span>
    @if ($embed)
        </button>
    @else
        </a>
    @endif

    <div class="flex flex-1 flex-col p-5">
        @if ($video->posted_at)
            <time datetime="{{ $video->posted_at->toDateString() }}" class="text-caption text-fg-muted">{{ $video->posted_at->format('d M Y') }}</time>
        @endif
        <h3 class="mt-1.5 text-h4 text-fg">{{ $video->title }}</h3>
        @if ($video->caption)
            <p class="mt-2 text-body-sm text-fg-secondary">{{ \Illuminate\Support\Str::limit($video->caption, 120) }}</p>
        @endif
        <a href="{{ $video->url }}" target="_blank" rel="noopener" class="mt-auto inline-flex items-center gap-1.5 pt-4 text-body-sm font-medium text-fg-secondary transition-colors hover:text-brand">
            View on {{ $label }}
            <x-ui.icon name="heroicon-m-arrow-top-right-on-square" class="size-4" />
        </a>
    </div>

    @if ($embed)
        <template x-teleport="body">
            <div x-show="open" x-transition.opacity class="fixed inset-0 z-[80] flex items-center justify-center bg-black/80 p-4 backdrop-blur-sm" role="dialog" aria-modal="true" aria-label="{{ $video->title }}" @click.self="open = false" x-cloak>
                <div class="relative w-full {{ $video->is_vertical ? 'max-w-sm' : 'max-w-4xl' }}">
                    <button type="button" class="absolute -top-12 right-0 inline-flex size-10 items-center justify-center rounded-pill bg-white/10 text-white transition hover:bg-white/20" @click="open = false" aria-label="Close video">
                        <x-ui.icon name="heroicon-o-x-mark" class="size-6" />
                    </button>
                    <div class="relative {{ $video->is_vertical ? 'aspect-[9/16] max-h-[80vh]' : 'aspect-video' }} overflow-hidden rounded-card bg-black">
                        <template x-if="open">
                            <iframe src="{{ $embed }}" title="{{ $video->title }}" class="absolute inset-0 size-full" allow="autoplay; encrypted-media; picture-in-picture; clipboard-write" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe>
                        </template>
                    </div>
                    <a href="{{ $video->url }}" target="_blank" rel="noopener" class="mt-3 inline-flex items-center gap-1.5 text-body-sm text-white/80 hover:text-white">
                        Open on {{ $label }}
                        <x-ui.icon name="heroicon-m-arrow-top-right-on-square" class="size-4" />
                    </a>
                </div>
            </div>
        </template>
    @endif
</article>
