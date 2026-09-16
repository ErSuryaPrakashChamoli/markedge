@props(['industry'])
@inject('urls', 'App\Services\Cms\PublicUrl')
<x-ui.card :href="$urls->pathFor($industry)" {{ $attributes->merge(['class' => 'justify-between']) }}>
    <div>
        <h3 class="text-h4">{{ $industry->name }}</h3>
        @if ($industry->tagline ?? $industry->short_description)
            <p class="mt-2 text-body-sm text-fg-secondary">{{ \Illuminate\Support\Str::limit($industry->tagline ?: $industry->short_description, 120) }}</p>
        @endif
    </div>
    <x-ui.icon name="heroicon-m-arrow-right" class="mt-4 size-4 text-fg-muted transition group-hover:translate-x-0.5 group-hover:text-brand" />
</x-ui.card>
