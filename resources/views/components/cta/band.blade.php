{{-- Full-width call-to-action band driven by a Cta model. --}}
@props(['cta', 'entity' => null, 'theme' => 'dark', 'heading' => null, 'body' => null])
@if ($cta)
    <x-ui.section :theme="$theme" spacing="sm" {{ $attributes }}>
        <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
            <div class="max-w-2xl">
                <p class="text-h2">{{ $heading ?? $cta->headline ?? $cta->primary_label }}</p>
                @if ($body ?? $cta->body)
                    <p class="mt-3 text-body-lg text-fg-secondary">{{ $body ?? $cta->body }}</p>
                @endif
            </div>
            <div class="flex flex-wrap gap-3">
                <x-cta.button :cta="$cta" :entity="$entity" size="lg" />
                <x-cta.button :cta="$cta" :entity="$entity" size="lg" secondary />
            </div>
        </div>
    </x-ui.section>
@endif
