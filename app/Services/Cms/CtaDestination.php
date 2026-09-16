<?php

namespace App\Services\Cms;

/**
 * Decides whether a CTA destination may be redirected to: internal paths, the site's own
 * absolute URLs, allow-listed external hosts, and tel:/mailto: links. Everything else is refused.
 */
class CtaDestination
{
    public function safe(?string $destination): ?string
    {
        if (! is_string($destination)) {
            return null;
        }

        $destination = trim(preg_replace('/[\x00-\x1F\x7F]+/u', '', $destination) ?? '');

        if ($destination === '' || mb_strlen($destination) > 2048) {
            return null;
        }

        if (preg_match('/^(tel|mailto):[^\s<>"\']+$/i', $destination) === 1) {
            return $destination;
        }

        if ($destination[0] === '/' && ! str_starts_with($destination, '//') && ! str_contains($destination, '\\')) {
            return $destination;
        }

        if (preg_match('/^https?:\/\//i', $destination) !== 1) {
            return null;
        }

        $host = mb_strtolower((string) parse_url($destination, PHP_URL_HOST));

        if ($host === '' || preg_match('/^[a-z0-9.-]+$/', $host) !== 1) {
            return null;
        }

        return in_array($host, $this->allowedHosts(), true) ? $destination : null;
    }

    /**
     * @return array<int, string>
     */
    protected function allowedHosts(): array
    {
        $hosts = array_merge(
            [mb_strtolower((string) parse_url(config('app.url'), PHP_URL_HOST))],
            (array) config('markedge.cta.allowed_external_hosts', []),
            (array) config('markedge.redirects.allowed_external_hosts', []),
        );

        return array_values(array_filter(array_map(fn ($host) => mb_strtolower(trim((string) $host)), $hosts)));
    }
}
