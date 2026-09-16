<?php

namespace App\Filament\Support;

use App\Editorial\ContentHealthAuditor;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class ContentHealthAction
{
    public static function make(): Action
    {
        return Action::make('contentHealth')
            ->label('Content health')
            ->icon(Heroicon::OutlinedHeart)
            ->color('gray')
            ->modalHeading('Content health')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Close')
            ->modalContent(fn (Model $record) => view('filament.modals.content-health', ['issues' => app(ContentHealthAuditor::class)->check($record->fresh())]))
            ->visible(fn (?Model $record): bool => $record !== null && method_exists($record, 'revisions') && Gate::allows('view', $record));
    }
}
