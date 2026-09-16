@props(['result'])
<li>
    <article class="border-t border-line py-6 first:border-t-0">
        <div class="flex flex-wrap items-center gap-2 text-caption text-fg-muted">
            <x-ui.badge tone="brand">{{ $result->typeLabel }}</x-ui.badge>
            @if ($result->context)
                <span>{{ $result->context }}</span>
            @endif
            @if ($result->publishedAt && in_array($result->type, ['article', 'case_study'], true))
                <time datetime="{{ $result->publishedAt->toDateString() }}">{{ $result->publishedAt->format('d M Y') }}</time>
            @endif
        </div>
        <h2 class="mt-2 text-h4"><a href="{{ $result->url }}" class="text-fg hover:text-brand focus-visible:outline-brand">{{ $result->title }}</a></h2>
        @if ($result->excerpt)
            <p class="mt-2 max-w-3xl text-body-sm text-fg-secondary">{{ $result->excerpt }}</p>
        @endif
        <p class="mt-2 text-caption text-fg-muted">{{ $result->url }}</p>
    </article>
</li>
