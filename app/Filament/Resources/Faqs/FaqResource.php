<?php

namespace App\Filament\Resources\Faqs;

use App\Filament\Resources\Faqs\Pages\ManageFaqs;
use App\Models\Article;
use App\Models\Faq;
use App\Models\Industry;
use App\Models\LandingPage;
use App\Models\Page;
use App\Models\Product;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Solution;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\MorphToSelect;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * All FAQs across the site. Each FAQ belongs to one entity; entity edit pages manage their own.
 */
class FaqResource extends Resource
{
    protected static ?string $model = Faq::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQuestionMarkCircle;

    protected static string|UnitEnum|null $navigationGroup = 'Insights';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'FAQs';

    protected static ?string $recordTitleAttribute = 'question';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            MorphToSelect::make('faqable')
                ->label('Belongs to')
                ->types([
                    MorphToSelect\Type::make(Page::class)->titleAttribute('title'),
                    MorphToSelect\Type::make(ServiceCategory::class)->titleAttribute('name'),
                    MorphToSelect\Type::make(Service::class)->titleAttribute('name'),
                    MorphToSelect\Type::make(Product::class)->titleAttribute('name'),
                    MorphToSelect\Type::make(Solution::class)->titleAttribute('name'),
                    MorphToSelect\Type::make(Industry::class)->titleAttribute('name'),
                    MorphToSelect\Type::make(Article::class)->titleAttribute('title'),
                    MorphToSelect\Type::make(LandingPage::class)->titleAttribute('title'),
                ])
                ->required()
                ->columnSpanFull(),
            TextInput::make('question')->required()->maxLength(255)->columnSpanFull(),
            RichEditor::make('answer')->required()->toolbarButtons(['bold', 'italic', 'bulletList', 'orderedList', 'link'])->columnSpanFull(),
            Toggle::make('is_visible')->label('Visible')->default(true)->inline(false),
            TextInput::make('sort_order')->numeric()->default(0),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('faqable'))
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('question')->searchable()->limit(70),
                TextColumn::make('faqable_type')->label('Belongs to')->badge()->color('gray'),
                TextColumn::make('faqable.name')->label('Record')->default(fn (Faq $record): string => $record->faqable?->title ?? $record->faqable?->name ?? '—'),
                IconColumn::make('is_visible')->label('Visible')->boolean(),
            ])
            ->filters([
                SelectFilter::make('faqable_type')->label('Belongs to')->options([
                    'page' => 'Page', 'service_category' => 'Service category', 'service' => 'Service', 'product' => 'Product',
                    'solution' => 'Solution', 'industry' => 'Industry', 'article' => 'Article', 'landing_page' => 'Landing page',
                ]),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageFaqs::route('/')];
    }
}
