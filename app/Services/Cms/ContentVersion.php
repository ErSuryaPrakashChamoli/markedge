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

    public function current(): int
    {
        return (int) Cache::rememberForever(self::KEY, fn (): int => 1);
    }

    public function bump(): int
    {
        $next = $this->current() + 1;

        Cache::forever(self::KEY, $next);

        return $next;
    }

    public function key(string $name): string
    {
        return "{$name}:v{$this->current()}";
    }
}
