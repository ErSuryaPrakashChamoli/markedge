<x-layouts.app :meta="$meta">
    <x-sections.entity-hero eyebrow="Insights" :title="$heading" :intro="$intro" :breadcrumbs="$breadcrumbs">
        @if ($categories->isNotEmpty())
            <nav class="mt-8" aria-label="Article categories">
                <ul class="flex flex-wrap gap-2">
                    <li><a href="{{ url('/insights') }}" @class(['inline-flex h-9 items-center rounded-pill border px-4 text-body-sm font-medium', 'border-brand bg-brand text-white' => $current === null && $author === null, 'border-line-strong text-fg-secondary hover:border-fg hover:text-fg' => $current !== null || $author !== null])>All</a></li>
                    @foreach ($categories as $category)
                        <li><a href="{{ url('/insights/category/'.$category->slug) }}" @class(['inline-flex h-9 items-center rounded-pill border px-4 text-body-sm font-medium', 'border-brand bg-brand text-white' => $current?->is($category), 'border-line-strong text-fg-secondary hover:border-fg hover:text-fg' => ! $current?->is($category)]) @if ($current?->is($category)) aria-current="page" @endif>{{ $category->name }} <span class="ml-1 opacity-70">{{ $category->articles_count }}</span></a></li>
                    @endforeach
                </ul>
            </nav>
        @endif
    </x-sections.entity-hero>

    @if ($author && filled(strip_tags((string) $author->bio)))
        <x-ui.section spacing="sm" container="narrow">
            <div class="flex items-start gap-5">
                @if ($avatar = $author->getFirstMedia('avatar'))
                    <img src="{{ $avatar->hasGeneratedConversion('thumb') ? $avatar->getUrl('thumb') : $avatar->getUrl() }}" alt="" class="size-16 rounded-full object-cover" width="64" height="64">
                @endif
                <x-ui.prose :html="$author->bio" />
            </div>
        </x-ui.section>
    @endif

    @if ($featured->isNotEmpty() && $current === null && $author === null)
        <x-ui.section spacing="sm">
            @foreach ($featured as $article)
                <x-cards.article :article="$article" featured />
            @endforeach
        </x-ui.section>
    @endif

    @if ($articles->isNotEmpty())
        <x-ui.section :spacing="$featured->isNotEmpty() ? 'sm' : 'default'">
            <x-ui.grid :cols="3">
                @foreach ($articles as $article)<x-cards.article :article="$article" />@endforeach
            </x-ui.grid>
            @if ($articles->hasPages())
                <div class="mt-12">{{ $articles->onEachSide(1)->links('pagination.site') }}</div>
            @endif
        </x-ui.section>
    @else
        <x-ui.section container="narrow"><p class="text-body-lg text-fg-secondary">Articles are being prepared. Check back soon.</p></x-ui.section>
    @endif

    @if ($cta)
        <x-cta.band :cta="$cta" />
    @endif
</x-layouts.app>
