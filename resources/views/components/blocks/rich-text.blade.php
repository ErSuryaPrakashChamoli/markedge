@props(['data', 'host' => null, 'preview' => false])
<x-ui.section :theme="$data['theme'] ?? 'light'" :id="$data['anchor'] ?? null" :container="($data['width'] ?? 'narrow') === 'narrow' ? 'prose' : 'default'" spacing="sm">
    <x-ui.prose :html="$data['body']" />
</x-ui.section>
