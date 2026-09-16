{{-- Article skeleton (architecture §14.2): readable width, body, related capabilities, author, CTA. --}}
@php $image = $entity->getFirstMedia('featured'); $avatar = $entity->author?->getFirstMedia('avatar'); @endphp
<x-layouts.app :meta="$meta" :preview="$preview">
    <article>
        <x-ui.section theme="dark" pattern="grid" spacing="sm" as="header">
            <x-layout.breadcrumbs :items="$breadcrumbs" class="mb-8" />
            <div class="max-w-3xl py-4 lg:py-8">
                @if ($entity->category)<x-ui.badge tone="brand">{{ $entity->category->name }}</x-ui.badge>@endif
                <h1 class="mt-4 text-h1">{{ $entity->title }}</h1>
                @if ($entity->excerpt)<p class="mt-5 text-body-lg text-fg-secondary">{{ $entity->excerpt }}</p>@endif
                <div class="mt-6 flex flex-wrap items-center gap-x-5 gap-y-2 text-body-sm text-fg-secondary">
                    @if ($entity->author)
                        <span class="inline-flex items-center gap-2">
                            @if ($avatar)<img src="{{ $avatar->hasGeneratedConversion('thumb') ? $avatar->getUrl('thumb') : $avatar->getUrl() }}" alt="" class="size-8 rounded-full object-cover" width="32" height="32">@endif
                            <a href="{{ url('/insights/author/'.$entity->author->slug) }}" class="font-medium text-fg hover:text-brand">{{ $entity->author->name }}</a>
                        </span>
                    @endif
                    @if ($entity->published_at)<time datetime="{{ $entity->published_at->toDateString() }}">{{ $entity->published_at->format('d F Y') }}</time>@endif
                    @if ($entity->updated_at && $entity->published_at && $entity->updated_at->gt($entity->published_at->addDay()))<span>Updated <time datetime="{{ $entity->updated_at->toDateString() }}">{{ $entity->updated_at->format('d F Y') }}</time></span>@endif
                    <span>{{ $entity->reading_time_minutes }} min read</span>
                </div>
            </div>
        </x-ui.section>

        @if ($image)
            <x-ui.section spacing="none" container="narrow" as="div" class="-mt-6">
                <x-ui.picture :media="$image" conversion="hero" priority sizes="(min-width: 1024px) 56rem, 100vw" />
            </x-ui.section>
        @endif

        <x-ui.section container="prose" as="div">
            <x-ui.prose :html="$entity->body" />
            @if ($entity->tags->isNotEmpty())
                <ul class="mt-10 flex flex-wrap gap-2" aria-label="Tags">
                    @foreach ($entity->tags as $tag)<li><a href="{{ url('/insights/tag/'.$tag->slug) }}" class="inline-flex h-8 items-center rounded-pill border border-line px-3 text-caption text-fg-secondary hover:border-fg hover:text-fg" rel="tag">{{ $tag->name }}</a></li>@endforeach
                </ul>
            @endif
        </x-ui.section>

        @if ($entity->author && filled(strip_tags((string) $entity->author->bio)))
            <x-ui.section theme="neutral" spacing="sm" container="prose" as="aside">
                <div class="flex items-start gap-5">
                    @if ($avatar)<img src="{{ $avatar->hasGeneratedConversion('thumb') ? $avatar->getUrl('thumb') : $avatar->getUrl() }}" alt="" class="size-16 rounded-full object-cover" width="64" height="64" loading="lazy">@endif
                    <div>
                        <p class="text-eyebrow text-fg-muted">Written by</p>
                        <p class="mt-1 text-h4"><a href="{{ url('/insights/author/'.$entity->author->slug) }}" class="hover:text-brand">{{ $entity->author->name }}</a></p>
                        @if ($entity->author->role_title)<p class="text-body-sm text-fg-secondary">{{ $entity->author->role_title }}</p>@endif
                        <x-ui.prose :html="$entity->author->bio" class="mt-3 text-body-sm" />
                    </div>
                </div>
            </x-ui.section>
        @endif
    </article>

    <x-sections.faq-list :faqs="$entity->faqs" />

    @if ($related['services']->isNotEmpty() || $related['products']->isNotEmpty() || $related['solutions']->isNotEmpty() || $related['industries']->isNotEmpty())
        <x-ui.section theme="neutral">
            <x-ui.section-header title="Related capabilities" class="mb-10" />
            <x-ui.grid :cols="3">
                @foreach ($related['services'] as $service)<x-cards.service :service="$service" show-category />@endforeach
                @foreach ($related['products'] as $product)<x-cards.product :product="$product" />@endforeach
                @foreach ($related['solutions'] as $solution)<x-cards.solution :solution="$solution" />@endforeach
                @foreach ($related['industries'] as $industry)<x-cards.industry :industry="$industry" />@endforeach
            </x-ui.grid>
        </x-ui.section>
    @endif

    <x-sections.related-grid :items="$related['articles']" heading="Keep reading" card="article" />

    @if ($cta)
        <x-cta.band :cta="$cta" />
    @endif
</x-layouts.app>
