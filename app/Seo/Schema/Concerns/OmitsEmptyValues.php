<?php

namespace App\Seo\Schema\Concerns;

/**
 * Removes null, empty-string, empty-array and placeholder values so no property is ever invented.
 */
trait OmitsEmptyValues
{
    /**
     * @param  array<string, mixed>  $node
     * @return array<string, mixed>
     */
    protected function compact(array $node): array
    {
        return array_filter($node, function (mixed $value): bool {
            if ($value === null || $value === '' || $value === []) {
                return false;
            }

            return ! (is_string($value) && str_contains($value, '[PLACEHOLDER'));
        });
    }

    protected function text(?string $html): ?string
    {
        $text = trim(html_entity_decode(strip_tags((string) $html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        return $text === '' || str_contains($text, '[PLACEHOLDER') ? null : $text;
    }
}
