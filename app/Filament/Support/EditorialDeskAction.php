<?php

namespace App\Filament\Support;

use App\Filament\Pages\EditorialDesk;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class EditorialDeskAction
{
    public static function make(): Action
    {
        return Action::make('editorialDesk')
            ->label('Editorial desk')
            ->icon(Heroicon::OutlinedClipboardDocumentCheck)
            ->color('gray')
            ->url(fn (Model $record): string => EditorialDesk::getUrl(['type' => $record->getMorphClass(), 'record' => $record->getKey()]))
            ->visible(fn (?Model $record): bool => $record !== null && method_exists($record, 'revisions') && Gate::allows('view', $record));
    }
}
