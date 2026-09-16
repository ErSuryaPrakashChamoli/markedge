@props(['items', 'cta' => null, 'ctaHref' => null, 'phoneHref' => null, 'whatsappHref' => null])
<div
    id="mobile-nav"
    x-show="drawerOpen"
    x-cloak
    x-transition.opacity.duration.150ms
    class="fixed inset-x-0 top-16 bottom-0 z-40 overflow-y-auto border-t border-line bg-surface lg:hidden"
    role="dialog"
    aria-modal="true"
    aria-label="Site menu"
    x-data="accordion()"
>
    <nav class="gutter-px py-4" aria-label="Primary mobile">
        <ul class="divide-y divide-line">
            @foreach ($items as $index => $item)
                <li>
                    @if ($item->hasChildren())
                        <button
                            type="button"
                            @click="toggle({{ $index }})"
                            :aria-expanded="isActive({{ $index }})"
                            class="flex w-full items-center justify-between py-3.5 text-left text-h4 text-fg"
                        >
                            {{ $item->label }}
                            <x-ui.icon name="heroicon-m-chevron-down" class="size-5 text-fg-muted transition-transform" ::class="isActive({{ $index }}) && 'rotate-180'" />
                        </button>
                        <div x-show="isActive({{ $index }})" x-collapse x-cloak class="pb-3">
                            @if ($item->url)
                                <a href="{{ $item->url }}" class="block py-1.5 text-body-sm font-semibold text-brand">All {{ $item->label }}</a>
                            @endif
                            <ul class="space-y-1">
                                @foreach ($item->children as $child)
                                    @if ($child->hasChildren())
                                        <li class="pt-2">
                                            @if ($child->url)
                                                <a href="{{ $child->url }}" class="block py-1 text-nav text-fg">{{ $child->label }}</a>
                                            @else
                                                <span class="block py-1 text-eyebrow text-fg-muted">{{ $child->label }}</span>
                                            @endif
                                            <ul class="border-l border-line pl-3">
                                                @foreach ($child->children as $grandchild)
                                                    <li><a href="{{ $grandchild->url }}" class="block py-1.5 text-body-sm text-fg-secondary">{{ $grandchild->label }}</a></li>
                                                @endforeach
                                            </ul>
                                        </li>
                                    @else
                                        <li><a href="{{ $child->url }}" class="flex items-center justify-between py-1.5 text-nav text-fg-secondary">{{ $child->label }} @if ($child->badge)<x-ui.badge size="sm">{{ $child->badge }}</x-ui.badge>@endif</a></li>
                                    @endif
                                @endforeach
                            </ul>
                        </div>
                    @else
                        <a href="{{ $item->url }}" @if ($item->isActive) aria-current="page" @endif class="block py-3.5 text-h4 text-fg">{{ $item->label }}</a>
                    @endif
                </li>
            @endforeach
        </ul>

        <div class="mt-6 flex flex-col gap-3">
            @if ($cta && $ctaHref)
                <x-ui.button :href="$ctaHref" class="w-full">{{ $cta->primary_label }}</x-ui.button>
            @endif
            @if ($whatsappHref)
                <x-ui.button :href="$whatsappHref" variant="outline" class="w-full" target="_blank" rel="noopener">WhatsApp</x-ui.button>
            @endif
            @if ($phoneHref)
                <x-ui.button :href="$phoneHref" variant="ghost" class="w-full">Call us</x-ui.button>
            @endif
        </div>
    </nav>
</div>
