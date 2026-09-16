@props(['data', 'host' => null, 'preview' => false])
<x-sections.related-grid :items="$data['products']" :heading="filled($data['heading'] ?? null) ? $data['heading'] : 'Related products'" card="product" :cols="min(max($data['products']->count(), 2), 3)" :theme="$data['theme'] ?? 'light'" :id="$data['anchor'] ?? null" />
