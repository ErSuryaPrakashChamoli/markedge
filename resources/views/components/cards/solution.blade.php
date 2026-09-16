@props(['solution'])
@inject('urls', 'App\Services\Cms\PublicUrl')
<x-ui.card :href="$urls->pathFor($solution)" {{ $attributes }}>
    <h3 class="text-h4">{{ $solution->name }}</h3>
    @if ($solution->tagline ?? $solution->short_description)
        <p class="mt-2 text-body-sm text-fg-secondary">{{ \Illuminate\Support\Str::limit($solution->tagline ?: $solution->short_description, 140) }}</p>
    @endif
    <span class="mt-4 inline-flex items-center gap-1.5 text-button text-fg group-hover:text-brand">See the solution <x-ui.icon name="heroicon-m-arrow-right" class="size-4 transition-transform group-hover:translate-x-0.5" /></span>
</x-ui.card>
