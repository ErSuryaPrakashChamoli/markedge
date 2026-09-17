@props(['items'])
<nav class="hidden lg:block" aria-label="Primary">
    <ul class="flex items-center gap-1">
        @foreach ($items as $index => $item)
            @php $key = 'panel-'.$index; @endphp
            <li
                class="static"
                @if ($item->hasChildren())
                    @mouseenter="showPanel('{{ $key }}')"
                    @mouseleave="scheduleClose()"
                    @focusout="if (! $el.contains($event.relatedTarget)) openPanel = null"
                @endif
            >
                @if ($item->hasChildren())
                    <button
                        type="button"
                        @click="togglePanel('{{ $key }}')"
                        :aria-expanded="isOpen('{{ $key }}')"
                        @class([
                            'nav-underline inline-flex items-center gap-1 rounded-control px-3 py-2 text-nav transition-colors',
                            'text-fg' => $item->isActive,
                            'text-fg-secondary hover:text-fg' => ! $item->isActive,
                        ])
                    >
                        <span @class(['border-b-2 border-brand pb-0.5' => $item->isActive])>{{ $item->label }}</span>
                        <x-ui.icon name="heroicon-m-chevron-down" class="size-4 transition-transform" ::class="isOpen('{{ $key }}') && 'rotate-180'" />
                    </button>

                    {{-- Anchored to the header container's right edge so it always sits under the navigation and inside the viewport. --}}
                        <div
                            x-show="isOpen('{{ $key }}')"
                            x-cloak
                            x-transition.opacity.duration.150ms
                            x-data="{ group: 0 }"
                            class="absolute top-full right-[var(--spacing-gutter-lg)] z-40 w-[min(66rem,calc(100%-2*var(--spacing-gutter-lg)))] pt-2"
                        >
                            <div class="flex overflow-hidden rounded-card border border-line bg-surface-elevated shadow-overlay">
                                <ul class="w-60 shrink-0 border-r border-line bg-canvas-muted/40 p-3" role="list">
                                    @foreach ($item->children as $i => $group)
                                        <li>
                                            <a
                                                href="{{ $group->url ?? '#' }}"
                                                @mouseenter="group = {{ $i }}"
                                                @focus="group = {{ $i }}"
                                                :class="group === {{ $i }} ? 'bg-brand/10 text-fg' : 'text-fg-secondary hover:bg-canvas-muted hover:text-fg'"
                                                class="flex items-center justify-between gap-2 rounded-control px-3 py-2.5 text-nav transition-colors"
                                                :aria-current="group === {{ $i }} ? 'true' : null"
                                            >
                                                <span class="truncate">{{ $group->label }}</span>
                                                <span class="flex shrink-0 items-center gap-1">
                                                    @if ($group->badge)<x-ui.badge size="sm">{{ $group->badge }}</x-ui.badge>@endif
                                                    <x-ui.icon name="heroicon-m-chevron-right" class="size-4 text-fg-muted" x-show="group === {{ $i }}" x-cloak />
                                                </span>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>

                                <div class="min-w-0 flex-1 p-6">
                                    @foreach ($item->children as $i => $group)
                                        @php
                                            $sections = collect($group->children)->filter->hasChildren();
                                            $flat = collect($group->children)->reject->hasChildren();
                                        @endphp
                                        <div x-show="group === {{ $i }}" @if ($i !== 0) x-cloak @endif>
                                            <div class="flex items-start justify-between gap-6 border-b border-line pb-4">
                                                <div class="min-w-0">
                                                    <p class="text-h4 text-fg">{{ $group->label }}</p>
                                                    @if ($group->description)
                                                        <p class="mt-1 text-caption text-fg-muted">{{ $group->description }}</p>
                                                    @endif
                                                    @if ($group->url)
                                                        <a href="{{ $group->url }}" class="mt-1 inline-flex items-center gap-1 text-body-sm text-fg-secondary hover:text-brand">View overview <x-ui.icon name="heroicon-m-arrow-right" class="size-4" /></a>
                                                    @endif
                                                </div>
                                                @if ($group->url)
                                                    <x-ui.button :href="$group->url" size="sm">Explore {{ $group->label }}</x-ui.button>
                                                @endif
                                            </div>

                                            @if ($sections->isEmpty() && $flat->isEmpty())
                                                <p class="mt-5 max-w-prose text-body-sm text-fg-secondary">{{ $group->description ?: 'Open the page to learn more.' }}</p>
                                            @endif
                                            <div class="mt-5 grid grid-cols-3 gap-x-8 gap-y-1">
                                                @if ($sections->isNotEmpty())
                                                    @foreach ($sections as $section)
                                                        <div>
                                                            <p class="mb-2 px-3 text-eyebrow text-fg-muted">{{ $section->label }}</p>
                                                            <ul>
                                                                @foreach ($section->children as $child)
                                                                    <li><x-navigation.link :node="$child" compact /></li>
                                                                @endforeach
                                                            </ul>
                                                        </div>
                                                    @endforeach
                                                    @if ($flat->isNotEmpty())
                                                        <div>
                                                            <ul>@foreach ($flat as $child)<li><x-navigation.link :node="$child" compact /></li>@endforeach</ul>
                                                        </div>
                                                    @endif
                                                @else
                                                    @foreach ($flat->chunk(max(1, (int) ceil($flat->count() / 3))) as $chunk)
                                                        <ul>
                                                            @foreach ($chunk as $child)
                                                                <li><x-navigation.link :node="$child" compact /></li>
                                                            @endforeach
                                                        </ul>
                                                    @endforeach
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                @else
                    <a
                        href="{{ $item->url }}"
                        @if ($item->isActive) aria-current="page" @endif
                        @class([
                            'nav-underline inline-block rounded-control px-3 py-2 text-nav transition-colors',
                            'text-fg' => $item->isActive,
                            'text-fg-secondary hover:text-fg' => ! $item->isActive,
                        ])
                    >
                        <span @class(['border-b-2 border-brand pb-0.5' => $item->isActive])>{{ $item->label }}</span>
                    </a>
                @endif
            </li>
        @endforeach
    </ul>
</nav>
