@props(['items'])
<nav class="hidden lg:block" aria-label="Primary">
    <ul class="flex items-center gap-1">
        @foreach ($items as $index => $item)
            @php $key = 'panel-'.$index; @endphp
            <li
                class="relative"
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
                            'inline-flex items-center gap-1 rounded-control px-3 py-2 text-nav transition-colors',
                            'text-fg' => $item->isActive,
                            'text-fg-secondary hover:text-fg' => ! $item->isActive,
                        ])
                    >
                        <span @class(['border-b-2 border-brand pb-0.5' => $item->isActive])>{{ $item->label }}</span>
                        <x-ui.icon name="heroicon-m-chevron-down" class="size-4 transition-transform" ::class="isOpen('{{ $key }}') && 'rotate-180'" />
                    </button>

                    <div
                        x-show="isOpen('{{ $key }}')"
                        x-cloak
                        x-transition.opacity.duration.150ms
                        @class([
                            'absolute top-full z-40 pt-3',
                            'left-1/2 w-[min(64rem,calc(100vw-4rem))] -translate-x-1/2' => $item->isMegaMenu(),
                            'left-0 w-72' => ! $item->isMegaMenu(),
                        ])
                    >
                        <div class="rounded-card border border-line bg-surface-elevated p-2 shadow-overlay">
                            @if ($item->isMegaMenu())
                                <div class="grid grid-cols-{{ min(count($item->children), 3) }} gap-2 p-2">
                                    @foreach ($item->children as $group)
                                        <x-navigation.column :node="$group" />
                                    @endforeach
                                </div>
                            @else
                                <ul class="p-1">
                                    @foreach ($item->children as $child)
                                        @if ($child->hasChildren())
                                            <li class="px-3 pt-3 pb-1 text-eyebrow text-fg-muted">{{ $child->label }}</li>
                                            @foreach ($child->children as $grandchild)
                                                <li><x-navigation.link :node="$grandchild" /></li>
                                            @endforeach
                                        @else
                                            <li><x-navigation.link :node="$child" /></li>
                                        @endif
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </div>
                @else
                    <a
                        href="{{ $item->url }}"
                        @if ($item->isActive) aria-current="page" @endif
                        @class([
                            'inline-block rounded-control px-3 py-2 text-nav transition-colors',
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
