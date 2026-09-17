<?php

namespace App\Media;

use Imagick;

/**
 * Runtime capability check for next-gen formats. AVIF is only offered when the configured
 * image driver is Imagick and the build supports it; GD builds silently keep WebP only.
 */
class ImageFormats
{
    private static ?bool $avif = null;

    public static function supportsAvif(): bool
    {
        if (self::$avif !== null) {
            return self::$avif;
        }

        if (config('media-library.image_driver') !== 'imagick' || ! class_exists(Imagick::class)) {
            return self::$avif = false;
        }

        try {
            return self::$avif = in_array('AVIF', (new Imagick)->queryFormats('AVIF'), true);
        } catch (\Throwable) {
            return self::$avif = false;
        }
    }

    public static function forget(): void
    {
        self::$avif = null;
    }
}
