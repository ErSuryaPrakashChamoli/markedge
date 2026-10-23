<x-layouts.app :meta="$meta">
    <x-sections.entity-hero eyebrow="Resources" :title="$heading" :intro="$intro" :breadcrumbs="$breadcrumbs">
        @if (request()->routeIs('insights.index'))
            <nav class="mt-8" aria-label="Article types">
                <ul class="inline-flex flex-wrap gap-1 rounded-pill border border-line-strong p-1">
                    <li><a href="{{ url('/insights') }}" @class(['inline-flex h-9 items-center rounded-pill px-4 text-body-sm font-medium transition-colors', 'bg-fg text-canvas' => $type === null, 'text-fg-secondary hover:text-fg' => $type !== null]) @if ($type === null) aria-current="page" @endif>Everything</a></li>
                    @foreach ($types as $articleType)
                        <li><a href="{{ url('/insights?type='.$articleType->value) }}" @class(['inline-flex h-9 items-center rounded-pill px-4 text-body-sm font-medium transition-colors', 'bg-fg text-canvas' => $type === $articleType, 'text-fg-secondary hover:text-fg' => $type !== $articleType]) @if ($type === $articleType) aria-current="page" @endif>{{ $articleType->pluralLabel() }}</a></li>
                    @endforeach
                </ul>
            </nav>
        @endif
        @if ($categories->isNotEmpty() && $type === null)
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
        <x-ui.section container="narrow"><p class="text-body-lg text-fg-secondary">{{ $type ? $type->pluralLabel().' are being prepared.' : 'Articles are being prepared.' }} Check back soon.</p></x-ui.section>
    @endif

    @if ($cta)
        <x-cta.band :cta="$cta" />
    @endif
</x-layouts.app>
