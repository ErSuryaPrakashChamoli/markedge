<?php

namespace App\Services\Cms;

/**
 * Google Maps URLs for the contact map. Pasted "Embed a map" code is reduced to its iframe src on
 * google.com, so no markup from the setting ever reaches the page. Place searches need no API key.
 */
class GoogleMap
{
    public const string EMBED_PREFIX = 'https://www.google.com/maps/embed?';

    /**
     * The embed URL from Google's iframe code or a bare embed URL; null when the input is neither.
     */
    public static function embedSource(?string $input): ?string
    {
        $input = trim((string) $input);

        if (preg_match('/\bsrc\s*=\s*["\']([^"\']+)["\']/i', $input, $matches) === 1) {
            $input = html_entity_decode($matches[1]);
        }

        return str_starts_with($input, self::EMBED_PREFIX) ? $input : null;
    }

    public static function embedUrlFor(string $query): string
    {
        return 'https://www.google.com/maps?'.http_build_query(['q' => $query, 'z' => 15, 'output' => 'embed']);
    }

    public static function directionsUrlFor(string $query): string
    {
        return 'https://www.google.com/maps/search/?'.http_build_query(['api' => 1, 'query' => $query]);
    }
}
