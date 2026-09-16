@props(['data', 'host' => null, 'preview' => false])
<x-sections.lead-form :form="$data['form']" :context="$data['context']" :heading="$data['heading'] ?? null" :intro="$data['intro'] ?? null" :layout="$data['layout'] ?? 'card'" :preview="$preview" :theme="$data['theme'] ?? 'light'" :id="$data['anchor'] ?? null" />
