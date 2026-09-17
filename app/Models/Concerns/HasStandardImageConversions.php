<?php

namespace App\Models\Concerns;

use App\Media\Conversions;
use App\Media\ImageFormats;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Media library integration plus the standard responsive conversions shared by every
 * image-bearing model (Phase 10 §7).
 *
 * Photographic collections get WebP at 400/800/1600 and, when the driver supports it, AVIF at
 * the same widths. Brand assets (logos, avatars) keep the original and a WebP thumb/card only:
 * no AVIF, no aggressive quality, transparency preserved. OG images stay JPEG for crawlers.
 */
trait HasStandardImageConversions
{
    use InteractsWithMedia;

    public function registerMediaConversions(?Media $media = null): void
    {
        foreach (Conversions::WIDTHS as $name => $width) {
            $this->addMediaConversion($name)->width($width)->format('webp')->quality(82)->performOnCollections(...Conversions::PHOTO_COLLECTIONS);

            if (ImageFormats::supportsAvif()) {
                $this->addMediaConversion("{$name}_avif")->width($width)->format('avif')->quality(65)->performOnCollections(...Conversions::PHOTO_COLLECTIONS);
            }
        }

        $this->addMediaConversion('thumb')->width(400)->format('webp')->quality(92)->performOnCollections(...Conversions::BRAND_COLLECTIONS);
        $this->addMediaConversion('card')->width(800)->format('webp')->quality(92)->performOnCollections(...Conversions::BRAND_COLLECTIONS);

        $this->addMediaConversion('og')->width(1200)->height(630)->format('jpg')->quality(85);
    }
}
