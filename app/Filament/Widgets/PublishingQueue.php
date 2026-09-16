<?php

namespace App\Filament\Widgets;

use App\Enums\PublishStatus;
use App\Services\Cms\Publisher;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Gate;

/**
 * Content waiting on someone: in review, scheduled, and recently published.
 */
class PublishingQueue extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'Publishing queue';

    public static function canView(): bool
    {
        return collect(app(Publisher::class)->publishableModels())->contains(fn (string $model): bool => Gate::allows('viewAny', $model));
    }

    protected function getStats(): array
    {
        $models = collect(app(Publisher::class)->publishableModels())->filter(fn (string $model): bool => Gate::allows('viewAny', $model));

        $count = fn (\Closure $scope): int => $models->sum(fn (string $model): int => $scope($model::query()));

        return [
            Stat::make('Awaiting review', $count(fn ($q) => $q->where('status', PublishStatus::Review)->count())),
            Stat::make('Scheduled', $count(fn ($q) => $q->scheduled()->count()))->description('publish automatically at their date'),
            Stat::make('Published this week', $count(fn ($q) => $q->published()->where('published_at', '>=', now()->startOfWeek())->count())),
        ];
    }
}
