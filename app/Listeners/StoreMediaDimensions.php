<?php

namespace App\Listeners;

use Spatie\MediaLibrary\MediaCollections\Events\MediaHasBeenAddedEvent;
use Throwable;

/**
 * Records intrinsic width and height on every uploaded image so templates can reserve space
 * (no layout shift) and derive the dimensions of each responsive variant.
 */
class StoreMediaDimensions
{
    public function handle(MediaHasBeenAddedEvent $event): void
    {
        $media = $event->media;

        if (! str_starts_with((string) $media->mime_type, 'image/') || $media->hasCustomProperty('width')) {
            return;
        }

        try {
            $size = @getimagesize($media->getPath());
        } catch (Throwable) {
            $size = false;
        }

        if (is_array($size) && $size[0] > 0 && $size[1] > 0) {
            $media->setCustomProperty('width', (int) $size[0]);
            $media->setCustomProperty('height', (int) $size[1]);
            $media->saveQuietly();
        }
    }
}
