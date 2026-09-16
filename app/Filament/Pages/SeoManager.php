<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Articles\ArticleResource;
use App\Filament\Resources\CaseStudies\CaseStudyResource;
use App\Filament\Resources\Industries\IndustryResource;
use App\Filament\Resources\LandingPages\LandingPageResource;
use App\Filament\Resources\Pages\PageResource;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\ServiceCategories\ServiceCategoryResource;
use App\Filament\Resources\Services\ServiceResource;
use App\Filament\Resources\Solutions\SolutionResource;
use App\Models\Article;
use App\Models\CaseStudy;
use App\Models\Industry;
use App\Models\LandingPage;
use App\Models\Page as CmsPage;
use App\Models\Product;
use App\Models\SeoMeta;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Solution;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use UnitEnum;

/**
 * One place for the SEO Manager: coverage per content type and every SEO record (architecture §15.5).
 */
class SeoManager extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMagnifyingGlass;

    protected static string|UnitEnum|null $navigationGroup = 'SEO';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'SEO Manager';

    protected string $view = 'filament.pages.seo-manager';

    /** @var array<string, class-string<Model>> */
    public const array TYPES = [
        'page' => CmsPage::class,
        'service_category' => ServiceCategory::class,
        'service' => Service::class,
        'product' => Product::class,
        'solution' => Solution::class,
        'industry' => Industry::class,
        'case_study' => CaseStudy::class,
        'article' => Article::class,
        'landing_page' => LandingPage::class,
    ];

    public static function canAccess(): bool
    {
        return Gate::allows('seo.view_any');
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $coverage = [];

        foreach (self::TYPES as $type => $model) {
            $query = $model::query();
            $visible = $model === Product::class ? $query->publiclyVisible() : $query->published();
            $total = (clone $visible)->count();

            $coverage[] = [
                'type' => ucfirst(str_replace('_', ' ', $type)),
                'published' => $total,
                'with_title' => (clone $visible)->whereHas('seo', fn (Builder $q) => $q->whereNotNull('title')->where('title', '!=', ''))->count(),
                'with_description' => (clone $visible)->whereHas('seo', fn (Builder $q) => $q->whereNotNull('description')->where('description', '!=', ''))->count(),
                'noindex' => (clone $visible)->whereHas('seo', fn (Builder $q) => $q->where('robots_index', false))->count(),
            ];
        }

        return ['coverage' => $coverage];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(SeoMeta::query()->with('seoable'))
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('seoable_type')->label('Type')->badge()->color('gray')->formatStateUsing(fn (string $state): string => ucfirst(str_replace('_', ' ', $state))),
                TextColumn::make('record')->label('Record')->state(fn (SeoMeta $record): string => $record->seoable?->title ?? $record->seoable?->name ?? '#'.$record->seoable_id)->searchable(false),
                TextColumn::make('title')->label('SEO title')->limit(50)->placeholder('Fallback'),
                TextColumn::make('description')->label('Description')->limit(50)->placeholder('Fallback')->toggleable(),
                IconColumn::make('robots_index')->label('Index')->boolean(),
                IconColumn::make('include_in_sitemap')->label('Sitemap')->boolean(),
                TextColumn::make('updated_at')->label('Updated')->since(),
            ])
            ->filters([
                SelectFilter::make('seoable_type')->label('Type')->options(array_combine(array_keys(self::TYPES), array_map(fn (string $t): string => ucfirst(str_replace('_', ' ', $t)), array_keys(self::TYPES)))),
                SelectFilter::make('missing')->options(['title' => 'Missing SEO title', 'description' => 'Missing description'])
                    ->query(fn (Builder $query, array $data): Builder => match ($data['value'] ?? null) {
                        'title' => $query->where(fn (Builder $q) => $q->whereNull('title')->orWhere('title', '')),
                        'description' => $query->where(fn (Builder $q) => $q->whereNull('description')->orWhere('description', '')),
                        default => $query,
                    }),
            ])
            ->recordActions([
                Action::make('edit')->label('Edit record')->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(fn (SeoMeta $record): ?string => static::editUrlFor($record))
                    ->visible(fn (SeoMeta $record): bool => static::editUrlFor($record) !== null),
            ]);
    }

    public static function editUrlFor(SeoMeta $record): ?string
    {
        $owner = $record->seoable;

        if ($owner === null) {
            return null;
        }

        $resource = match ($owner::class) {
            CmsPage::class => PageResource::class,
            ServiceCategory::class => ServiceCategoryResource::class,
            Service::class => ServiceResource::class,
            Product::class => ProductResource::class,
            Solution::class => SolutionResource::class,
            Industry::class => IndustryResource::class,
            CaseStudy::class => CaseStudyResource::class,
            Article::class => ArticleResource::class,
            LandingPage::class => LandingPageResource::class,
            default => null,
        };

        return $resource && Gate::allows('update', $owner) ? $resource::getUrl('edit', ['record' => $owner, 'tab' => '-seo-tab']) : null;
    }
}
