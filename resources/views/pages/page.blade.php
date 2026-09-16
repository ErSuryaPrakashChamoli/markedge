{{-- Generic CMS page. The template enum picks the frame; blocks provide the body (architecture §6.1). --}}
@php
    $template = $entity->template;
    $isForm = $template === \App\Enums\PageTemplate::Form || $template === \App\Enums\PageTemplate::Contact;
    $hasFormBlock = collect($blocks)->contains(fn (array $block): bool => in_array($block['key'], ['lead_form', 'contact_form'], true));
    $legal = $template === \App\Enums\PageTemplate::Legal;
    $firstIsHero = ($blocks[0]['key'] ?? null) === 'hero';
@endphp
<x-layouts.app :meta="$meta" :preview="$preview">
    @unless ($firstIsHero)
        <x-sections.entity-hero :title="$entity->title" :intro="$entity->excerpt" :breadcrumbs="$breadcrumbs" :theme="$legal ? 'neutral' : 'dark'" />
    @endunless

    @if ($legal && $entity->published_at)
        <x-ui.section spacing="sm" container="prose" as="div">
            <p class="text-caption text-fg-muted">Last updated {{ $entity->updated_at->format('d F Y') }}</p>
        </x-ui.section>
    @endif

    <x-cms.blocks :blocks="$blocks" :host="$entity" :preview="$preview" />

    @if ($isForm && ! $hasFormBlock && $entity->form && $entity->form->is_active)
        @if ($template === \App\Enums\PageTemplate::Contact)
            <x-ui.section>
                <x-ui.split ratio="wide-right" align="start">
                    <x-slot:left>
                        <h2 class="text-h2">{{ $entity->form->heading ?? 'Contact Markedge' }}</h2>
                        @if ($entity->form->intro)<p class="mt-4 text-body-lg text-fg-secondary">{{ $entity->form->intro }}</p>@endif
                        <x-blocks.partials.contact-details class="mt-8" />
                    </x-slot:left>
                    <x-slot:right>
                        <livewire:lead-form :form="$entity->form" :preview="$preview" layout="card" :key="'page-form-'.$entity->form->id" />
                    </x-slot:right>
                </x-ui.split>
            </x-ui.section>
        @else
            <x-sections.lead-form :form="$entity->form" :preview="$preview" />
        @endif
    @endif

    @if ($entity->faqs->isNotEmpty())
        <x-sections.faq-list :faqs="$entity->faqs" theme="neutral" />
    @endif

    @if ($cta && ! $isForm)
        <x-cta.band :cta="$cta" />
    @endif
</x-layouts.app>
