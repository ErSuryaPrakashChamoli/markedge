<?php

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGeneratorFactory;

/**
 * Admin uploads used to land on the private `local` disk (Filament's FILESYSTEM_DISK fallback), so the
 * public site linked to files it cannot serve. Every media collection is public content: move those
 * files, conversions included, onto the media library disk and point the rows at it.
 */
return new class extends Migration
{
    public function up(): void
    {
        $targetDisk = config('media-library.disk_name');

        if ($targetDisk === 'local') {
            return;
        }

        $source = Storage::disk('local');
        $destination = Storage::disk($targetDisk);

        Media::query()
            ->where(fn (Builder $query) => $query->where('disk', 'local')->orWhere('conversions_disk', 'local'))
            ->eachById(function (Media $media) use ($source, $destination, $targetDisk): void {
                $directory = rtrim(PathGeneratorFactory::create($media)->getPath($media), '/');

                foreach ($source->allFiles($directory) as $file) {
                    if (! $destination->put($file, $source->get($file))) {
                        throw new RuntimeException("Could not copy media file [{$file}] to the [{$targetDisk}] disk.");
                    }
                }

                Media::query()->whereKey($media->getKey())->update(['disk' => $targetDisk, 'conversions_disk' => $targetDisk]);

                $source->deleteDirectory($directory);
            });
    }

    /**
     * Not reversed: moving the files back to the private disk would break the public site again.
     */
    public function down(): void
    {
        //
    }
};
