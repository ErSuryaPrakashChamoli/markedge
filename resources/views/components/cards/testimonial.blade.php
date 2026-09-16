@props(['testimonial'])
@php $avatar = $testimonial->relationLoaded('media') ? $testimonial->getFirstMedia('avatar') : null; @endphp
<figure {{ $attributes->merge(['class' => 'flex h-full flex-col rounded-card border border-line bg-surface card-p']) }}>
    <blockquote class="flex-1 text-body text-fg-secondary">
        <p>“{{ $testimonial->quote }}”</p>
    </blockquote>
    <figcaption class="mt-5 flex items-center gap-3">
        @if ($avatar)
            <img src="{{ $avatar->hasGeneratedConversion('thumb') ? $avatar->getUrl('thumb') : $avatar->getUrl() }}" alt="" class="size-10 rounded-full object-cover" width="40" height="40" loading="lazy" decoding="async">
        @endif
        <div>
            <p class="text-body-sm font-semibold text-fg">{{ $testimonial->author_name }}</p>
            <p class="text-caption text-fg-muted">{{ implode(', ', array_filter([$testimonial->author_role, $testimonial->company_name ?: ($testimonial->relationLoaded('client') ? $testimonial->client?->name : null)])) }}</p>
        </div>
    </figcaption>
</figure>
