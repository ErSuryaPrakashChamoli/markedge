@props(['data', 'host' => null, 'preview' => false])
<x-ui.section :theme="$data['theme'] ?? 'neutral'" :id="$data['anchor'] ?? null" spacing="sm">
    @if (filled($data['heading'] ?? null))
        <x-ui.section-header :title="$data['heading']" align="center" size="h3" class="mb-8" />
    @endif
    <ul class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-{{ min(max($data['clients']->count(), 3), 6) }}">
        @foreach ($data['clients'] as $client)
            <li><x-cards.client-logo :client="$client" /></li>
        @endforeach
    </ul>
</x-ui.section>
