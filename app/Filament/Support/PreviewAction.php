<?php

namespace App\Filament\Support;

use App\Services\Cms\PreviewLink;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;

class PreviewAction
{
    public static function make(): Action
    {
        return Action::make('preview')
            ->label('Preview')
            ->icon(Heroicon::OutlinedEye)
            ->color('gray')
            ->url(fn (Model $record): string => app(PreviewLink::class)->for($record))
            ->openUrlInNewTab()
            ->visible(fn (?Model $record): bool => $record !== null
                && app(PreviewLink::class)->supports($record)
                && auth()->user()?->can('preview', $record));
    }
}
