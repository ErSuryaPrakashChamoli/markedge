@props(['data', 'host' => null, 'preview' => false])
<x-sections.newsletter :heading="$data['heading'] ?? null" :intro="$data['intro'] ?? null" :button-label="$data['button_label'] ?? null" :theme="$data['theme'] ?? 'light'" :id="$data['anchor'] ?? null" />
