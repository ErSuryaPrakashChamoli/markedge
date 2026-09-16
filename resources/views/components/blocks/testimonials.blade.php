@props(['data', 'host' => null, 'preview' => false])
<x-ui.section :theme="$data['theme'] ?? 'light'" :id="$data['anchor'] ?? null">
    @if (filled($data['heading'] ?? null))
        <x-ui.section-header :title="$data['heading']" class="mb-10" />
    @endif
    <x-ui.grid :cols="min(max($data['testimonials']->count(), 2), 3)">
        @foreach ($data['testimonials'] as $testimonial)
            <x-cards.testimonial :testimonial="$testimonial" />
        @endforeach
    </x-ui.grid>
</x-ui.section>
