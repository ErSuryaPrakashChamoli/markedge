<?php

namespace App\Search;

/**
 * Cleans public search input without changing its meaning: trims, collapses whitespace,
 * removes control characters, bounds the length and tokenises. Case is normalised only
 * for matching; the original wording is preserved for display.
 */
class QueryNormalizer
{
    public const int MIN_LENGTH = 2;

    public const int MAX_LENGTH = 120;

    public const int MAX_TOKENS = 12;

    public function normalise(?string $input): string
    {
        $value = (string) $input;
        $value = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $value) ?? '';
        $value = preg_replace('/\s+/u', ' ', trim($value)) ?? '';

        if (mb_strlen($value) > self::MAX_LENGTH) {
            $value = rtrim(mb_substr($value, 0, self::MAX_LENGTH));
        }

        return $value;
    }

    public function isTooShort(string $term): bool
    {
        return mb_strlen($term) < self::MIN_LENGTH;
    }

    /**
     * Lower-cased word tokens with search operators stripped, bounded in number.
     *
     * @return array<int, string>
     */
    public function tokens(string $term): array
    {
        $cleaned = preg_replace('/[^\p{L}\p{N}\s\'-]+/u', ' ', mb_strtolower($term)) ?? '';
        $tokens = preg_split('/\s+/u', trim($cleaned), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $tokens = array_values(array_unique(array_filter(
            array_map(fn (string $token): string => trim($token, "'-"), $tokens),
            fn (string $token): bool => mb_strlen($token) >= 2,
        )));

        return array_slice($tokens, 0, self::MAX_TOKENS);
    }
}
