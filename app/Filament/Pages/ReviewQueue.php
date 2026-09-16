<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Support\Icons\Heroicon;

/**
 * The inventory narrowed to content that is waiting on someone: in review or scheduled.
 */
class ReviewQueue extends ContentInventory
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Review queue';

    protected static ?string $slug = 'review-queue';

    public string $sort = 'submitted_at';

    public function mount(): void
    {
        if ($this->status === []) {
            $this->status = ['review', 'scheduled'];
        }
    }

    protected function getViewData(): array
    {
        return ['queue' => true] + parent::getViewData();
    }
}
