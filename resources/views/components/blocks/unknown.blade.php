@props(['data', 'host' => null, 'preview' => false])
@if ($preview)
    <x-ui.section spacing="sm">
        <x-ui.card>
            <x-ui.badge tone="danger" class="self-start">Block {{ $data['position'] }}</x-ui.badge>
            <p class="mt-3 text-body text-fg-secondary">The section type "{{ $data['label'] }}" is not registered for this page and will not render publicly.</p>
        </x-ui.card>
    </x-ui.section>
@endif
