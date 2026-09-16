<?php

namespace App\Filament\Support;

use App\Enums\PublishStatus;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;

/**
 * Table columns and filters repeated across resources.
 */
class Columns
{
    public static function status(): TextColumn
    {
        return TextColumn::make('status')->badge()->sortable();
    }

    public static function statusFilter(): SelectFilter
    {
        return SelectFilter::make('status')->options(PublishStatus::class)->multiple();
    }

    public static function featured(): IconColumn
    {
        return IconColumn::make('is_featured')->label('Featured')->boolean()->sortable()->toggleable();
    }

    public static function publishedAt(): TextColumn
    {
        return TextColumn::make('published_at')->label('Published')->dateTime('d M Y H:i')->sortable()->toggleable();
    }

    public static function updatedAt(): TextColumn
    {
        return TextColumn::make('updated_at')->label('Updated')->since()->sortable()->toggleable(isToggledHiddenByDefault: true);
    }

    public static function sortOrder(): TextColumn
    {
        return TextColumn::make('sort_order')->label('Order')->sortable()->toggleable(isToggledHiddenByDefault: true);
    }
}
