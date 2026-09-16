<?php

namespace App\Filament\Widgets;

use App\Enums\ProductStatus;
use App\Enums\PublishStatus;
use App\Models\Article;
use App\Models\CaseStudy;
use App\Models\Page;
use App\Models\Product;
use App\Models\Service;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Gate;

class ContentOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $heading = 'Website content';

    public static function canView(): bool
    {
        return Gate::any(['viewAny'], Page::class) || Gate::allows('viewAny', Article::class) || Gate::allows('viewAny', Product::class);
    }

    protected function getStats(): array
    {
        $stats = [];

        if (Gate::allows('viewAny', Page::class)) {
            $stats[] = Stat::make('Published pages', Page::query()->published()->count())->description(Page::query()->where('status', PublishStatus::Draft)->count().' drafts');
        }

        if (Gate::allows('viewAny', Service::class)) {
            $stats[] = Stat::make('Published services', Service::query()->published()->count())->description(Service::query()->count().' in catalogue');
        }

        if (Gate::allows('viewAny', Product::class)) {
            $stats[] = Stat::make('Visible products', Product::query()->publiclyVisible()->count())->description(Product::query()->where('status', ProductStatus::ComingSoon)->count().' coming soon');
        }

        if (Gate::allows('viewAny', Article::class)) {
            $stats[] = Stat::make('Published articles', Article::query()->published()->count())->description(Article::query()->where('status', PublishStatus::Review)->count().' awaiting review');
        }

        if (Gate::allows('viewAny', CaseStudy::class)) {
            $stats[] = Stat::make('Case studies', CaseStudy::query()->published()->count())->description('published');
        }

        return $stats;
    }
}
