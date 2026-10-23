@props(['data', 'host' => null, 'preview' => false])
@php
    $videos = $data['videos'];
    $platforms = $videos->pluck('platform')->filter()->unique()->values();
@endphp
<x-ui.section :theme="$data['theme'] ?? 'light'" :id="$data['anchor'] ?? null">
    <div x-data="{ platform: 'all' }">
        <div class="mb-10 flex flex-wrap items-end justify-between gap-6">
            <x-ui.section-header :title="filled($data['heading'] ?? null) ? $data['heading'] : 'Videos'" :intro="$data['intro'] ?? null" />
            @if ($platforms->count() > 1)
                <div class="flex flex-wrap gap-2" role="group" aria-label="Filter videos by platform">
                    <button type="button" @click="platform = 'all'" :aria-pressed="platform === 'all'" class="inline-flex h-9 items-center rounded-pill border px-4 text-body-sm font-medium transition-colors" :class="platform === 'all' ? 'border-brand bg-brand text-white' : 'border-line-strong text-fg-secondary hover:border-fg hover:text-fg'">All</button>
                    @foreach ($platforms as $platform)
                        <button type="button" @click="platform = '{{ $platform->value }}'" :aria-pressed="platform === '{{ $platform->value }}'" class="inline-flex h-9 items-center gap-2 rounded-pill border px-4 text-body-sm font-medium transition-colors" :class="platform === '{{ $platform->value }}' ? 'border-brand bg-brand text-white' : 'border-line-strong text-fg-secondary hover:border-fg hover:text-fg'">
                            <x-ui.social-icon :platform="$platform->value" class="size-4" />
                            {{ $platform->getLabel() }}
                        </button>
                    @endforeach
                </div>
            @endif
        </div>
        <x-ui.grid :cols="4">
            @foreach ($videos as $video)
                <x-cards.social-video :video="$video" x-show="platform === 'all' || platform === '{{ $video->platform?->value }}'" />
            @endforeach
        </x-ui.grid>
    </div>
</x-ui.section>
