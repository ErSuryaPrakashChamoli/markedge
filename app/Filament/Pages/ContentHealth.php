<?php

namespace App\Filament\Pages;

use App\Editorial\ContentHealthReport;
use App\Editorial\WorkflowModels;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\Url;
use UnitEnum;

/**
 * Operational content health: factual counts and bounded lists, no score.
 */
class ContentHealth extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHeart;

    protected static string|UnitEnum|null $navigationGroup = 'Editorial';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Content health';

    protected string $view = 'filament.pages.content-health';

    #[Url]
    public string $list = 'awaiting_review';

    public static function canAccess(): bool
    {
        return ContentInventory::visibleTypes() !== [];
    }

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('rescan')->label('Rescan blocks and links')->icon(Heroicon::OutlinedArrowPath)->color('gray')->action(function (): void {
                app(ContentHealthReport::class)->rescan();
                Notification::make()->title('Scan refreshed.')->success()->send();
            }),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        abort_unless(static::canAccess(), 403);

        $types = ContentInventory::visibleTypes();
        $report = app(ContentHealthReport::class);
        $labels = [
            'awaiting_review' => 'Awaiting review', 'scheduled' => 'Scheduled', 'published_recently' => 'Published (7 days)', 'modified_recently' => 'Modified (7 days)',
            'expired' => 'Expired (unpublish date passed)', 'missing_summary' => 'Missing summary and SEO description', 'missing_image' => 'Missing required image',
            'missing_author' => 'Missing author', 'invalid_blocks' => 'Invalid blocks', 'broken_links' => 'Broken internal links', 'orphaned_pages' => 'Orphaned pages (no menu or content link)',
        ];
        $list = array_key_exists($this->list, $labels) ? $this->list : 'awaiting_review';

        return [
            'counts' => $report->counts($types),
            'labels' => $labels,
            'selected' => $list,
            'items' => $report->list($list, $types),
            'typeLabels' => collect(WorkflowModels::all())->keys()->mapWithKeys(fn (string $alias) => [$alias => WorkflowModels::label($alias)]),
        ];
    }

    public function deskUrl(string $type, int $id): string
    {
        return EditorialDesk::getUrl(['type' => $type, 'record' => $id]);
    }
}
