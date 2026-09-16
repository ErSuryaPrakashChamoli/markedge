@props(['article', 'featured' => false])
@inject('urls', 'App\Services\Cms\PublicUrl')
@php $image = $article->relationLoaded('media') ? $article->getFirstMedia('featured') : null; @endphp
<x-ui.card :href="$urls->pathFor($article)" :padding="false" {{ $attributes->merge(['class' => 'overflow-hidden']) }}>
    @if ($image)
        <x-ui.picture :media="$image" :conversion="$featured ? 'hero' : 'card'" class="rounded-none" :priority="$featured" :sizes="$featured ? '(min-width: 1024px) 66vw, 100vw' : '(min-width: 1024px) 33vw, 100vw'" />
    @endif
    <div class="card-p">
        <div class="flex flex-wrap items-center gap-2 text-caption text-fg-muted">
            @if ($article->relationLoaded('category') && $article->category)
                <x-ui.badge tone="brand">{{ $article->category->name }}</x-ui.badge>
            @endif
            @if ($article->published_at)
                <time datetime="{{ $article->published_at->toDateString() }}">{{ $article->published_at->format('d M Y') }}</time>
            @endif
            <span aria-hidden="true">·</span>
            <span>{{ $article->reading_time_minutes }} min read</span>
        </div>
        <h3 class="mt-3 {{ $featured ? 'text-h2' : 'text-h4' }}">{{ $article->title }}</h3>
        @if ($article->excerpt)
            <p class="mt-2 text-body-sm text-fg-secondary">{{ \Illuminate\Support\Str::limit($article->excerpt, $featured ? 240 : 140) }}</p>
        @endif
        @if ($article->relationLoaded('author') && $article->author)
            <p class="mt-4 text-caption text-fg-muted">By {{ $article->author->name }}</p>
        @endif
    </div>
</x-ui.card>
