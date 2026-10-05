<?php

namespace App\Filament\Support;

use Filament\Forms\Components\SpatieMediaLibraryFileUpload;

/**
 * Upload fields with the approved file-type policy (architecture §24.2).
 * SVG is rejected everywhere until an SVG sanitiser is added; AVIF output is deferred.
 * Uploads always go to the media library disk: Filament would otherwise fall back to FILESYSTEM_DISK
 * (private `local`), whose files the public site cannot serve.
 */
class MediaFields
{
    /** @var array<int, string> */
    public const array IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    /** @var array<int, string> */
    public const array DOCUMENT_TYPES = ['application/pdf'];

    public static function image(string $collection, string $label, ?string $helper = null): SpatieMediaLibraryFileUpload
    {
        return SpatieMediaLibraryFileUpload::make($collection)
            ->label($label)
            ->collection($collection)
            ->disk(self::disk())
            ->image()
            ->imageEditor()
            ->acceptedFileTypes(self::IMAGE_TYPES)
            ->maxSize(5120)
            ->helperText($helper ?? 'JPG, PNG or WebP up to 5 MB. Add alt text in the Media Library after upload.');
    }

    public static function gallery(string $collection, string $label): SpatieMediaLibraryFileUpload
    {
        return static::image($collection, $label)
            ->multiple()
            ->reorderable()
            ->maxFiles(24)
            ->panelLayout('grid');
    }

    public static function documents(string $collection, string $label): SpatieMediaLibraryFileUpload
    {
        return SpatieMediaLibraryFileUpload::make($collection)
            ->label($label)
            ->collection($collection)
            ->disk(self::disk())
            ->acceptedFileTypes(self::DOCUMENT_TYPES)
            ->maxSize(20480)
            ->multiple()
            ->downloadable()
            ->helperText('PDF up to 20 MB.');
    }

    public static function disk(): string
    {
        return config('media-library.disk_name');
    }
}
