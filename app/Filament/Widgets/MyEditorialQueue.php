<?php

namespace App\Filament\Widgets;

use App\Editorial\ContentInventoryQuery;
use App\Enums\EditorialCommentType;
use App\Filament\Pages\ContentInventory;
use App\Filament\Pages\ReviewQueue;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Query\Builder;

/**
 * What the signed-in editor or reviewer is responsible for, aggregated in SQL.
 */
class MyEditorialQueue extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'My content';

    public static function canView(): bool
    {
        return ContentInventory::visibleTypes() !== [];
    }

    protected function getStats(): array
    {
        $user = auth()->id();
        $types = ContentInventory::visibleTypes();
        $base = fn (): Builder => app(ContentInventoryQuery::class)->base($types);
        $days = (int) config('markedge.editorial.expiry_reminder_days', 3);

        $changesRequested = $base()->where('owner_id', $user)->where('status', 'draft')->whereExists(fn ($q) => $q->selectRaw('1')->from('editorial_comments')
            ->whereColumn('editorial_comments.commentable_id', 'inventory.id')->whereColumn('editorial_comments.commentable_type', 'inventory.type')
            ->where('editorial_comments.type', EditorialCommentType::ChangeRequest->value)->whereNull('editorial_comments.resolved_at'))->count();

        return [
            Stat::make('Assigned to me', $base()->where('owner_id', $user)->where('status', '!=', 'archived')->count())->url(ContentInventory::getUrl(['owner' => $user])),
            Stat::make('Awaiting my review', $base()->where('reviewer_id', $user)->where('status', 'review')->count())->url(ReviewQueue::getUrl(['reviewer' => $user])),
            Stat::make('Changes requested', $changesRequested)->description('on content I own'),
            Stat::make('Scheduled', $base()->where('owner_id', $user)->where('status', 'scheduled')->count())->url(ContentInventory::getUrl(['owner' => $user, 'status' => ['scheduled']])),
            Stat::make('Published this week', $base()->where('owner_id', $user)->where('status', 'published')->where('published_at', '>=', now()->startOfWeek())->count()),
            Stat::make('Expiring soon', $base()->where('owner_id', $user)->where('status', 'published')->whereNotNull('unpublish_at')->whereBetween('unpublish_at', [now(), now()->addDays($days)])->count())->description("within {$days} days"),
        ];
    }
}
