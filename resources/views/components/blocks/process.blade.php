@props(['data', 'host' => null, 'preview' => false])
<x-sections.process-steps :steps="$data['steps']" :heading="$data['heading']" :intro="$data['intro'] ?? null" :orientation="$data['orientation'] ?? 'horizontal'" :theme="$data['theme'] ?? 'light'" :id="$data['anchor'] ?? null" />
