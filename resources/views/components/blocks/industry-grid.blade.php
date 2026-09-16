@props(['data', 'host' => null, 'preview' => false])
<x-sections.related-grid :items="$data['industries']" :heading="filled($data['heading'] ?? null) ? $data['heading'] : 'Industries'" :intro="$data['intro'] ?? null" card="industry" :cols="4" :theme="$data['theme'] ?? 'light'" :id="$data['anchor'] ?? null" more-label="All industries" :more-href="url('/industries')" />
