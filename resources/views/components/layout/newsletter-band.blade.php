{{--
    Site-wide newsletter sign-up, the top tier of the footer (architecture §29: "newsletter/CTA band").
    Switched on and worded in Global Settings → Newsletter. Pages that already place a Newsletter block skip it.
--}}
@inject('settings', 'App\Services\Cms\Settings')
@if ($settings->get('newsletter.footer_enabled', true))
    <section id="newsletter-footer" data-theme="dark" class="relative scroll-mt-28 overflow-hidden border-b border-line bg-canvas-dark text-fg" aria-label="Newsletter">
        <div class="pointer-events-none absolute -left-24 -bottom-40 size-[26rem] rounded-full bg-brand/10 blur-3xl" aria-hidden="true"></div>
        <x-ui.container class="relative py-12 lg:py-14">
            <x-sections.newsletter-form placement="footer" />
        </x-ui.container>
    </section>
@endif
