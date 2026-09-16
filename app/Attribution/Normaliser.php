<?php

namespace App\Attribution;

/**
 * Bounds and cleans untrusted attribution input (UTM values, referrers, paths).
 */
final class Normaliser
{
    public static function maxLength(): int
    {
        return (int) config('markedge.attribution.max_length', 120);
    }

    public static function bound(?string $value, bool $lower = false): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = preg_replace('/[\x00-\x1F\x7F]+/u', '', $value) ?? '';
        $value = trim(preg_replace('/\s+/u', ' ', $value) ?? '');

        if (! mb_check_encoding($value, 'UTF-8')) {
            return null;
        }

        if ($value === '') {
            return null;
        }

        $value = mb_substr($value, 0, self::maxLength());

        return $lower ? mb_strtolower($value) : $value;
    }

    /**
     * An internal path only: no scheme, no host, no protocol-relative form, bounded length.
     */
    public static function path(?string $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        $value = preg_replace('/[\x00-\x1F\x7F\s]+/u', '', $value) ?? '';

        if ($value === '' || $value[0] !== '/' || str_starts_with($value, '//') || str_contains($value, '\\') || preg_match('/^\/[a-z][a-z0-9+.-]*:/i', $value)) {
            return null;
        }

        $path = parse_url($value, PHP_URL_PATH);

        if (! is_string($path) || $path === '' || preg_match('/^\/[A-Za-z0-9\-._~%\/]*$/', $path) !== 1) {
            return null;
        }

        return mb_substr(rtrim($path, '/') ?: '/', 0, 255);
    }

    /**
     * Referrer reduced to its host (no query, no path, no personal data).
     */
    public static function referrerHost(?string $referrer): ?string
    {
        if (! is_string($referrer) || $referrer === '') {
            return null;
        }

        $host = parse_url(trim($referrer), PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return null;
        }

        $host = mb_strtolower($host);

        return preg_match('/^[a-z0-9.-]+$/', $host) === 1 ? mb_substr($host, 0, 120) : null;
    }
}
