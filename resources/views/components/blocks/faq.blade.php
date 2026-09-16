@props(['data', 'host' => null, 'preview' => false])
<x-sections.faq-list :faqs="$data['faqs']" :heading="filled($data['heading'] ?? null) ? $data['heading'] : 'Frequently asked questions'" :intro="$data['intro'] ?? null" :theme="$data['theme'] ?? 'light'" :id="$data['anchor'] ?? null" />
