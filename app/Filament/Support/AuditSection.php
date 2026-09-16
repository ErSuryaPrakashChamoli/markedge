<?php

namespace App\Filament\Support;

use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;

class AuditSection
{
    public static function make(bool $withAuthorship = true): Section
    {
        $fields = [
            Placeholder::make('created_at')->label('Created')->content(fn (?Model $record): string => $record?->created_at?->diffForHumans() ?? '—'),
            Placeholder::make('updated_at')->label('Last updated')->content(fn (?Model $record): string => $record?->updated_at?->diffForHumans() ?? '—'),
        ];

        if ($withAuthorship) {
            $fields[] = Placeholder::make('creator')->label('Created by')->content(fn (?Model $record): string => $record?->creator?->name ?? '—');
            $fields[] = Placeholder::make('editor')->label('Last edited by')->content(fn (?Model $record): string => $record?->editor?->name ?? '—');
        }

        return Section::make('Record information')
            ->icon(Heroicon::OutlinedClock)
            ->schema($fields)
            ->columns(2)
            ->collapsible()
            ->collapsed()
            ->hiddenOn('create');
    }
}
