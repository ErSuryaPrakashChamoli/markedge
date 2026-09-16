<?php

namespace App\Filament\Support;

use App\Seo\Diagnostics\SeoAudit;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class SeoDiagnosticsAction
{
    public static function make(): Action
    {
        return Action::make('seoDiagnostics')
            ->label('SEO checks')
            ->icon(Heroicon::OutlinedMagnifyingGlass)
            ->color('gray')
            ->modalHeading('SEO checks')
            ->modalDescription('Factual checks only. Required items must pass; recommended and informational items never block publishing.')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Close')
            ->modalContent(fn (Model $record) => view('filament.seo-diagnostics', ['checks' => app(SeoAudit::class)->forEntity($record)]))
            ->visible(fn (?Model $record): bool => $record !== null && (Gate::allows('view', $record) || Gate::allows('seo.view_any')));
    }
}
