{{-- Resources teaser: one tab per non-empty stream (blogs, news, social videos), each linking to its full listing. --}}
@props(['data', 'host' => null, 'preview' => false])
@php
    $tabs = collect([
        ['key' => 'blogs', 'label' => 'Blogs', 'items' => $data['blogs'], 'card' => 'article', 'more' => url('/insights?type=blog'), 'moreLabel' => 'All blogs'],
        ['key' => 'news', 'label' => 'News', 'items' => $data['news'], 'card' => 'article', 'more' => url('/insights?type=news'), 'moreLabel' => 'All news'],
        ['key' => 'videos', 'label' => 'Videos', 'items' => $data['videos'], 'card' => 'video', 'more' => url('/resources#videos'), 'moreLabel' => 'All videos'],
    ])->filter(fn (array $tab): bool => $tab['items']->isNotEmpty())->values();
    $moreUrl = filled($data['more_url'] ?? null) ? $data['more_url'] : '/resources';
    $uid = 'resources-'.($data['anchor'] ?? 'hub');
@endphp
<x-ui.section :theme="$data['theme'] ?? 'light'" :id="$data['anchor'] ?? null">
    <div x-data="{ tab: '{{ $tabs->first()['key'] }}' }">
        <div class="mb-10 flex flex-wrap items-end justify-between gap-6">
            <x-ui.section-header eyebrow="Resources" :title="filled($data['heading'] ?? null) ? $data['heading'] : 'Learn from our latest work'" :intro="$data['intro'] ?? null" />
            <x-ui.arrow-link :href="str_starts_with($moreUrl, 'http') ? $moreUrl : url($moreUrl)">{{ filled($data['more_label'] ?? null) ? $data['more_label'] : 'Explore all resources' }}</x-ui.arrow-link>
        </div>

        @if ($tabs->count() > 1)
            <div class="mb-8 flex flex-wrap gap-2 border-b border-line" role="tablist" aria-label="Resource types">
                @foreach ($tabs as $tab)
                    <button type="button" role="tab" id="{{ $uid }}-tab-{{ $tab['key'] }}" aria-controls="{{ $uid }}-panel-{{ $tab['key'] }}" @click="tab = '{{ $tab['key'] }}'" :aria-selected="tab === '{{ $tab['key'] }}'" :tabindex="tab === '{{ $tab['key'] }}' ? 0 : -1"
                        class="-mb-px inline-flex items-center gap-2 border-b-2 px-4 pb-3 text-button transition-colors" :class="tab === '{{ $tab['key'] }}' ? 'border-brand text-fg' : 'border-transparent text-fg-secondary hover:text-fg'">
                        {{ $tab['label'] }}
                        <span class="rounded-pill bg-canvas-muted px-2 py-0.5 text-meta text-fg-muted">{{ $tab['items']->count() }}</span>
                    </button>
                @endforeach
            </div>
        @endif

        @foreach ($tabs as $tab)
            <div id="{{ $uid }}-panel-{{ $tab['key'] }}" @if ($tabs->count() > 1) role="tabpanel" aria-labelledby="{{ $uid }}-tab-{{ $tab['key'] }}" @endif x-show="tab === '{{ $tab['key'] }}'" @unless ($loop->first) x-cloak @endunless>
                <x-ui.grid :cols="3">
                    @foreach ($tab['items'] as $item)
                        @if ($tab['card'] === 'video')
                            <x-cards.social-video :video="$item" />
                        @else
                            <x-cards.article :article="$item" />
                        @endif
                    @endforeach
                </x-ui.grid>
                <div class="mt-8">
                    <x-ui.arrow-link :href="$tab['more']">{{ $tab['moreLabel'] }}</x-ui.arrow-link>
                </div>
            </div>
        @endforeach
    </div>
</x-ui.section>
