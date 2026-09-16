@props(['data', 'host' => null, 'preview' => false])
<x-sections.related-grid :items="$data['articles']" :heading="filled($data['heading'] ?? null) ? $data['heading'] : 'Insights'" :intro="$data['intro'] ?? null" card="article" :cols="3" :theme="$data['theme'] ?? 'light'" :id="$data['anchor'] ?? null" more-label="All insights" :more-href="url('/insights')" />
