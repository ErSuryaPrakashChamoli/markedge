<?php

namespace App\Models\Concerns;

use App\Services\Cms\ContentVersion;

/**
 * Any change to a content model invalidates every content cache key
 * by incrementing the global content version (see architecture §32).
 */
trait BumpsContentVersion
{
    public static function bootBumpsContentVersion(): void
    {
        $bump = fn () => app(ContentVersion::class)->bump();

        static::saved($bump);
        static::deleted($bump);

        if (method_exists(static::class, 'restored')) {
            static::restored($bump);
        }
    }
}
