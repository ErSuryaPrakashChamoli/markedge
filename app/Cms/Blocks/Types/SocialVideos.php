<?php

namespace App\Cms\Blocks\Types;

use App\Cms\Blocks\Block;
use App\Cms\Blocks\Fields;
use App\Enums\SocialPlatform;
use App\Models\SocialVideo;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;

/**
 * Grid of social videos (Resources → Social videos in the admin). Visitors can filter by platform
 * when the grid holds more than one.
 */
class SocialVideos extends Block
{
    public function key(): string
    {
        return 'social_videos';
    }

    public function label(): string
    {
        return 'Social videos';
    }

    public function icon(): Heroicon
    {
        return Heroicon::OutlinedVideoCamera;
    }

    public function fields(): array
    {
        return [
            Fields::heading(),
            Fields::intro(),
            Fields::mode(['latest' => 'All visible videos', 'featured' => 'Featured videos', 'platforms' => 'Videos from chosen platforms', 'ids' => 'Selected videos'], 'latest'),
            Select::make('platforms')->label('Platforms')->options(SocialPlatform::class)->multiple()->native(false)
                ->visible(fn (Get $get): bool => $get('mode') === 'platforms')->required(fn (Get $get): bool => $get('mode') === 'platforms'),
            Fields::records('social_video_ids', 'Videos', SocialVideo::class, 'title')->visible(fn (Get $get): bool => $get('mode') === 'ids'),
            Fields::limit(8, 24),
        ];
    }

    public function rules(): array
    {
        return [
            'heading' => ['nullable', 'string', 'max:255'],
            'intro' => ['nullable', 'string', 'max:500'],
            'mode' => ['required', 'in:latest,featured,platforms,ids'],
            'platforms' => ['required_if:mode,platforms', 'nullable', 'array'],
            'platforms.*' => ['string', 'in:'.implode(',', array_column(SocialPlatform::cases(), 'value'))],
            'social_video_ids' => ['required_if:mode,ids', 'nullable', 'array'],
            'social_video_ids.*' => ['integer'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:24'],
        ];
    }
}
