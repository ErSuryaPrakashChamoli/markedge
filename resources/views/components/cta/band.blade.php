{{-- Full-width call-to-action band driven by a Cta model. --}}
@props(['cta', 'entity' => null, 'theme' => 'dark', 'heading' => null, 'body' => null])
@if ($cta)
    <x-ui.section theme="light" spacing="sm" {{ $attributes }}>
        <div data-theme="dark" class="relative overflow-hidden rounded-[1.75rem] bg-canvas bg-mesh px-8 py-12 text-fg shadow-lift md:px-14 md:py-16">
            <div class="orb -top-32 right-[-10%] size-[26rem] bg-brand/50" aria-hidden="true"></div>
            <div class="orb bottom-[-40%] left-[-5%] size-[20rem] bg-[color:var(--color-sky-500)]/30 [animation-delay:-7s]" aria-hidden="true"></div>
            <div class="relative flex flex-col gap-8 lg:flex-row lg:items-center lg:justify-between">
                <div class="max-w-2xl">
                    <p class="text-h2">{{ $heading ?? $cta->headline ?? $cta->primary_label }}</p>
                    @if ($body ?? $cta->body)
                        <p class="mt-4 text-body-lg text-fg-secondary">{{ $body ?? $cta->body }}</p>
                    @endif
                </div>
                <div class="flex flex-wrap gap-3">
                    <x-cta.button :cta="$cta" :entity="$entity" size="lg" />
                    <x-cta.button :cta="$cta" :entity="$entity" size="lg" secondary />
                </div>
            </div>
        </div>
    </x-ui.section>
@endif
