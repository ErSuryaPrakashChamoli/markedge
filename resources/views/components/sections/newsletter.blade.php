{{-- Newsletter block section: the sign-up inside a card. The footer uses x-layout.newsletter-band instead. --}}
@props(['heading' => null, 'intro' => null, 'buttonLabel' => null, 'theme' => 'light', 'id' => null])
<x-ui.section :theme="$theme" :id="$id" spacing="sm" data-newsletter-block>
    <div id="newsletter-block" class="relative scroll-mt-28 overflow-hidden rounded-[1.75rem] border border-line bg-surface px-6 py-10 shadow-card md:px-12 md:py-14">
        <div class="orb -top-28 right-[-8%] size-[22rem] bg-brand/15" aria-hidden="true"></div>
        <x-sections.newsletter-form :heading="$heading" :intro="$intro" :button-label="$buttonLabel" placement="block" class="relative" />
    </div>
</x-ui.section>
