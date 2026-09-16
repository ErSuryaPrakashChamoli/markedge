@props(['data', 'host' => null, 'preview' => false])
<x-sections.feature-list :items="$data['items']" :heading="$data['heading']" :intro="$data['intro'] ?? null" :columns="(int) ($data['columns'] ?? 3)" :theme="$data['theme'] ?? 'light'" :id="$data['anchor'] ?? null" />
