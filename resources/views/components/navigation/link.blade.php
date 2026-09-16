@props(['node', 'compact' => false])
<a
    href="{{ $node->url ?? '#' }}"
    @if ($node->openInNewTab) target="_blank" rel="noopener" @endif
    @if ($node->isActive) aria-current="page" @endif
    @class([
        'flex items-center justify-between gap-3 rounded-control px-3 hover:bg-canvas-muted',
        'py-1.5 text-body-sm' => $compact,
        'py-2 text-nav' => ! $compact,
        'text-fg' => $node->isActive,
        'text-fg-secondary hover:text-fg' => ! $node->isActive,
    ])
>
    <span class="truncate">{{ $node->label }}</span>
    @if ($node->badge)
        <x-ui.badge size="sm">{{ $node->badge }}</x-ui.badge>
    @endif
</a>
