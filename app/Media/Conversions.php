<?php

namespace App\Media;

/**
 * The responsive conversion set (names, widths and which collections receive them). Kept on a
 * class because PHP does not allow trait constants to be read from outside the using class.
 */
final class Conversions
{
    /** @var array<string, int> conversion name => width in pixels */
    public const array WIDTHS = ['thumb' => 400, 'card' => 800, 'hero' => 1600];

    /** @var array<int, string> photographs and screenshots: WebP + AVIF at every width */
    public const array PHOTO_COLLECTIONS = ['hero', 'featured', 'gallery', 'blocks', 'screenshots', 'image'];

    /** @var array<int, string> brand assets: original kept, gentle WebP thumb/card only, no AVIF */
    public const array BRAND_COLLECTIONS = ['logo', 'avatar'];
}
