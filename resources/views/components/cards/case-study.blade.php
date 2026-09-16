@props(['caseStudy'])
@inject('urls', 'App\Services\Cms\PublicUrl')
@php $hero = $caseStudy->relationLoaded('media') ? $caseStudy->getFirstMedia('hero') : null; @endphp
<x-ui.card :href="$urls->pathFor($caseStudy)" :padding="false" {{ $attributes->merge(['class' => 'overflow-hidden']) }}>
    @if ($hero)
        <x-ui.picture :media="$hero" conversion="card" class="rounded-none" sizes="(min-width: 1024px) 33vw, 100vw" />
    @endif
    <div class="card-p">
        <div class="flex flex-wrap gap-2">
            @if ($caseStudy->relationLoaded('industry') && $caseStudy->industry)
                <x-ui.badge>{{ $caseStudy->industry->name }}</x-ui.badge>
            @endif
            @if ($caseStudy->relationLoaded('client') && $caseStudy->client?->is_visible)
                <x-ui.badge tone="brand">{{ $caseStudy->client->name }}</x-ui.badge>
            @endif
        </div>
        <h3 class="mt-3 text-h4">{{ $caseStudy->title }}</h3>
        @if ($caseStudy->excerpt)
            <p class="mt-2 text-body-sm text-fg-secondary">{{ \Illuminate\Support\Str::limit($caseStudy->excerpt, 140) }}</p>
        @endif
        <span class="mt-4 inline-flex items-center gap-1.5 text-button text-fg group-hover:text-brand">Read the case study <x-ui.icon name="heroicon-m-arrow-right" class="size-4 transition-transform group-hover:translate-x-0.5" /></span>
    </div>
</x-ui.card>
