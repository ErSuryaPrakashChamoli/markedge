@props(['data', 'host' => null, 'preview' => false])
<x-sections.related-grid :items="$data['caseStudies']" :heading="filled($data['heading'] ?? null) ? $data['heading'] : 'Selected work'" :intro="$data['intro'] ?? null" card="case-study" :cols="3" :theme="$data['theme'] ?? 'light'" :id="$data['anchor'] ?? null" more-label="All case studies" :more-href="url('/case-studies')" />
