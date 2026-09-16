@props(['node'])
<div class="min-w-0">
    @if ($node->url)
        <a href="{{ $node->url }}" class="group flex items-start justify-between gap-2 rounded-control px-3 py-2 hover:bg-canvas-muted">
            <span>
                <span class="block text-h4 text-fg">{{ $node->label }}</span>
                @if ($node->description)
                    <span class="mt-0.5 block text-caption text-fg-muted">{{ $node->description }}</span>
                @endif
            </span>
            <x-ui.icon name="heroicon-m-arrow-right" class="mt-1 size-4 shrink-0 text-fg-muted transition group-hover:translate-x-0.5 group-hover:text-brand" />
        </a>
    @else
        <p class="px-3 py-2 text-eyebrow text-fg-muted">{{ $node->label }}</p>
    @endif
    @if ($node->hasChildren())
        <ul class="mt-1 border-t border-line pt-1">
            @foreach ($node->children as $child)
                <li><x-navigation.link :node="$child" compact /></li>
            @endforeach
        </ul>
    @endif
</div>
