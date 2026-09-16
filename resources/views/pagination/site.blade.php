{{-- Design-system pagination for public archives. Passed explicitly so panel defaults never leak in. --}}
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination" class="flex items-center justify-between gap-4">
        <div>
            @if ($paginator->onFirstPage())
                <span class="inline-flex h-10 items-center rounded-control border border-line px-4 text-body-sm text-fg-muted" aria-disabled="true">Previous</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex h-10 items-center rounded-control border border-line-strong px-4 text-body-sm font-medium text-fg hover:border-fg">Previous</a>
            @endif
        </div>

        <ul class="hidden items-center gap-1 sm:flex">
            @foreach ($elements as $element)
                @if (is_string($element))
                    <li><span class="px-2 text-body-sm text-fg-muted" aria-hidden="true">{{ $element }}</span></li>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <li>
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page" class="inline-flex size-10 items-center justify-center rounded-control bg-brand text-body-sm font-semibold text-white">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="inline-flex size-10 items-center justify-center rounded-control text-body-sm text-fg-secondary hover:bg-canvas-muted hover:text-fg" aria-label="Go to page {{ $page }}">{{ $page }}</a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach
        </ul>
        <p class="text-caption text-fg-muted sm:hidden">Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</p>

        <div>
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex h-10 items-center rounded-control border border-line-strong px-4 text-body-sm font-medium text-fg hover:border-fg">Next</a>
            @else
                <span class="inline-flex h-10 items-center rounded-control border border-line px-4 text-body-sm text-fg-muted" aria-disabled="true">Next</span>
            @endif
        </div>
    </nav>
@endif
