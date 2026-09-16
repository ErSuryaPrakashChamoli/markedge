<?php

namespace App\Filament\Pages;

use App\Search\SearchIndexer;
use App\Search\SearchTypes;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Gate;
use UnitEnum;

/**
 * Factual search index health with a chunked rebuild. Never touches CMS data.
 */
class SearchIndexStatus extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMagnifyingGlass;

    protected static string|UnitEnum|null $navigationGroup = 'SEO';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Search index';

    protected string $view = 'filament.pages.search-index-status';

    public static function canAccess(): bool
    {
        return Gate::allows('seo.view_any');
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $audit = app(SearchIndexer::class)->audit();

        return [
            'audit' => $audit,
            'labels' => collect(SearchTypes::TYPES)->map(fn (array $definition): string => $definition['plural']),
            'healthy' => collect($audit['by_type'])->every(fn (array $row): bool => $row['missing'] === 0 && $row['stale'] === 0),
        ];
    }

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('rebuild')
                ->label('Rebuild index')
                ->icon(Heroicon::OutlinedArrowPath)
                ->requiresConfirmation()
                ->modalDescription('Clears the search index and rebuilds it from published, indexable content. No page, product or setting is changed.')
                ->visible(fn (): bool => Gate::allows('seo.update'))
                ->action(function (): void {
                    abort_unless(Gate::allows('seo.update'), 403);
                    $count = app(SearchIndexer::class)->rebuild();
                    Notification::make()->title("Search index rebuilt with {$count} documents.")->success()->send();
                }),
        ];
    }
}
