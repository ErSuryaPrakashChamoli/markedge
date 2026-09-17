<?php

namespace App\Services\Cms;

use Illuminate\Support\Facades\Cache;

/**
 * Monotonic content version embedded in every content cache key.
 * Bumping it invalidates all content caches without cache tags,
 * which the database cache store does not support.
 */
class ContentVersion
{
    public const string KEY = 'content:version';

    /** Per-request memo (the service is scoped); saves one cache read per key derivation. */
    private ?int $current = null;

    public function current(): int
    {
        return $this->current ??= (int) Cache::rememberForever(self::KEY, fn (): int => 1);
    }

    public function bump(): int
    {
        $next = (int) Cache::rememberForever(self::KEY, fn (): int => 1) + 1;

        Cache::forever(self::KEY, $next);

        return $this->current = $next;
    }

    public function forget(): void
    {
        $this->current = null;
    }

    public function key(string $name): string
    {
        return "{$name}:v{$this->current()}";
    }
}
