@props(['data', 'host' => null, 'preview' => false])
<x-sections.related-grid :items="$data['services']" :heading="filled($data['heading'] ?? null) ? $data['heading'] : 'Related services'" card="service" :cols="min(max($data['services']->count(), 2), 4)" :theme="$data['theme'] ?? 'light'" :id="$data['anchor'] ?? null" />
