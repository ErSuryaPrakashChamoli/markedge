{{-- Campaign landing page (architecture §22): blocks in a conversion-focused shell, form injected when no block provides one. --}}
@php $hasForm = collect($blocks)->contains(fn (array $block): bool => in_array($block['key'], ['lead_form', 'contact_form'], true)); $firstIsHero = ($blocks[0]['key'] ?? null) === 'hero'; @endphp
<x-layouts.landing :meta="$meta" :hide-navigation="$entity->hide_navigation" :hide-footer-links="$entity->hide_footer_links" :preview="$preview">
    @if ($entity->tracking)
        @push('head')
            <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">window.dataLayer = window.dataLayer || []; window.dataLayer.push({!! json_encode(['event' => 'landing_page_view', 'landing_page' => $entity->slug, 'campaign' => $entity->campaign?->utm_campaign] + array_map('strval', $entity->tracking), JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!});</script>
        @endpush
    @endif

    @unless ($firstIsHero)
        <x-sections.entity-hero :title="$entity->title" :cta="$cta" :entity-name="$entity->title" />
    @endunless

    <x-cms.blocks :blocks="$blocks" :host="$entity" :preview="$preview" />

    @if (! $hasForm && $entity->form && $entity->form->is_active)
        <x-sections.lead-form :form="$entity->form" :context="['landing_page_id' => $entity->id]" :preview="$preview" theme="neutral" id="form" />
    @endif

    <x-sections.faq-list :faqs="$entity->faqs" />

    @if ($cta && ! $hasForm && ! $entity->form)
        <x-cta.band :cta="$cta" :entity="$entity->title" />
    @endif
</x-layouts.landing>
