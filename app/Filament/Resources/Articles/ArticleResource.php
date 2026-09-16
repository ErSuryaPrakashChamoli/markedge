<?php

namespace App\Filament\Resources\Articles;

use App\Filament\RelationManagers\FaqsRelationManager;
use App\Filament\Resources\Articles\Pages\CreateArticle;
use App\Filament\Resources\Articles\Pages\EditArticle;
use App\Filament\Resources\Articles\Pages\ListArticles;
use App\Filament\Support\AuditSection;
use App\Filament\Support\Columns;
use App\Filament\Support\CtaSelect;
use App\Filament\Support\MediaFields;
use App\Filament\Support\PreviewAction;
use App\Filament\Support\PublishActions;
use App\Filament\Support\PublishingFields;
use App\Filament\Support\RelationSelect;
use App\Filament\Support\SeoFields;
use App\Filament\Support\SlugField;
use App\Models\Article;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class ArticleResource extends Resource
{
    protected static ?string $model = Article::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected static string|UnitEnum|null $navigationGroup = 'Insights';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Article')->tabs([
                Tab::make('Content')->icon(Heroicon::OutlinedDocumentText)->schema([
                    Section::make()->schema([
                        SlugField::source('title', 'Title')->columnSpanFull(),
                        SlugField::make()->helperText('Public URL: /insights/{slug}.'),
                        Placeholder::make('reading_time')->label('Reading time')->content(fn (?Article $record): string => $record ? "{$record->reading_time_minutes} min (calculated from the body)" : 'Calculated on save'),
                        Textarea::make('excerpt')->required()->rows(3)->maxLength(500)->columnSpanFull(),
                        RichEditor::make('body')->required()->columnSpanFull(),
                        MediaFields::image('featured', 'Featured image')->columnSpanFull(),
                    ])->columns(2),
                ]),
                Tab::make('Classification')->icon(Heroicon::OutlinedTag)->schema([
                    Section::make()->schema([
                        Select::make('author_id')->label('Author')->relationship('author', 'name')->searchable()->preload()->native(false),
                        Select::make('article_category_id')->label('Category')->relationship('category', 'name')->searchable()->preload()->native(false),
                        Select::make('tags')->relationship('tags', 'name')->multiple()->searchable()->preload()
                            ->createOptionForm([TextInput::make('name')->required()->maxLength(60)])
                            ->columnSpanFull(),
                    ])->columns(2),
                ]),
                Tab::make('Relationships')->icon(Heroicon::OutlinedLink)->schema([
                    RelationSelect::many('services', 'Related services'),
                    RelationSelect::many('products', 'Related products'),
                    RelationSelect::many('industries', 'Related industries'),
                    RelationSelect::many('solutions', 'Related solutions'),
                    RelationSelect::many('relatedArticles', 'Related articles', 'title', preload: false),
                    CtaSelect::make(),
                ]),
                Tab::make('Publishing')->icon(Heroicon::OutlinedPaperAirplane)->schema([PublishingFields::make(Article::class, sortOrder: false), AuditSection::make()]),
                Tab::make('SEO')->icon(Heroicon::OutlinedMagnifyingGlass)->schema([SeoFields::make()]),
            ])->columnSpanFull()->persistTabInQueryString(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('title')->searchable()->sortable()->limit(60),
                TextColumn::make('author.name')->label('Author')->placeholder('—')->toggleable(),
                TextColumn::make('category.name')->label('Category')->badge()->color('gray')->placeholder('—'),
                Columns::status(),
                Columns::featured(),
                TextColumn::make('reading_time_minutes')->label('Read')->suffix(' min')->toggleable(isToggledHiddenByDefault: true),
                Columns::publishedAt(),
                Columns::updatedAt(),
            ])
            ->filters([
                Columns::statusFilter(),
                SelectFilter::make('article_category_id')->label('Category')->relationship('category', 'name')->preload(),
                SelectFilter::make('author_id')->label('Author')->relationship('author', 'name')->preload(),
                TrashedFilter::make(),
            ])
            ->recordActions([EditAction::make(), ActionGroup::make([PreviewAction::make(), ...PublishActions::record()])])
            ->toolbarActions([BulkActionGroup::make([...PublishActions::bulk(), DeleteBulkAction::make(), RestoreBulkAction::make()])]);
    }

    public static function getRelations(): array
    {
        return [FaqsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListArticles::route('/'),
            'create' => CreateArticle::route('/create'),
            'edit' => EditArticle::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
