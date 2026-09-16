<?php

namespace App\Models\Concerns;

use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Media library integration plus the standard responsive conversions shared by
 * every image-bearing model. AVIF variants are added in the performance phase
 * once driver support is verified.
 */
trait HasStandardImageConversions
{
    use InteractsWithMedia;

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')->width(400)->format('webp');
        $this->addMediaConversion('card')->width(800)->format('webp');
        $this->addMediaConversion('hero')->width(1600)->format('webp');
        $this->addMediaConversion('og')->width(1200)->height(630)->format('jpg');
    }
}
