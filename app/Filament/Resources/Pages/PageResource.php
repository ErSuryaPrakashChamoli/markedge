<?php

namespace App\Filament\Resources\Pages;

use App\Enums\PageTemplate;
use App\Enums\PublishStatus;
use App\Filament\RelationManagers\FaqsRelationManager;
use App\Filament\Resources\Pages\Pages\CreatePage;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Filament\Resources\Pages\Pages\ListPages;
use App\Filament\Support\AuditSection;
use App\Filament\Support\BlockBuilder;
use App\Filament\Support\Columns;
use App\Filament\Support\CtaSelect;
use App\Filament\Support\PreviewAction;
use App\Filament\Support\PublishActions;
use App\Filament\Support\PublishingFields;
use App\Filament\Support\SeoFields;
use App\Filament\Support\SlugField;
use App\Models\Page;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\ReplicateAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Website';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Page')->tabs([
                Tab::make('Content')->icon(Heroicon::OutlinedDocumentText)->schema([
                    Section::make()->schema([
                        SlugField::source('title', 'Title'),
                        SlugField::make(reserved: true)->helperText('Public URL: /{slug}. The home page uses the slug "home".'),
                        Select::make('template')->options(PageTemplate::class)->default(PageTemplate::Default)->required()->native(false),
                        Textarea::make('excerpt')->label('Summary')->rows(2)->maxLength(500)->helperText('Used for listings and as the fallback meta description.'),
                    ])->columns(2),
                    Section::make('Form and CTA')->schema([
                        Select::make('form_id')->label('Form (form and contact templates)')->relationship('form', 'name')->searchable()->preload()->native(false),
                        CtaSelect::make(),
                    ])->columns(2)->collapsible(),
                ]),
                Tab::make('Sections')->icon(Heroicon::OutlinedSquares2x2)->schema([
                    BlockBuilder::make('page'),
                ]),
                Tab::make('Publishing')->icon(Heroicon::OutlinedPaperAirplane)->schema([
                    PublishingFields::make(Page::class, featured: false, sortOrder: false),
                    AuditSection::make(),
                ]),
                Tab::make('SEO')->icon(Heroicon::OutlinedMagnifyingGlass)->schema([
                    SeoFields::make(),
                ]),
            ])->columnSpanFull()->persistTabInQueryString(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('title')
            ->columns([
                TextColumn::make('title')->searchable()->sortable()->description(fn (Page $record): string => '/'.($record->isHome() ? '' : $record->slug)),
                TextColumn::make('template')->badge()->sortable(),
                Columns::status(),
                Columns::publishedAt(),
                Columns::updatedAt(),
            ])
            ->filters([
                Columns::statusFilter(),
                SelectFilter::make('template')->options(PageTemplate::class),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                ActionGroup::make([
                    PreviewAction::make(),
                    ...PublishActions::record(),
                    ReplicateAction::make()->excludeAttributes(['slug', 'published_at'])->beforeReplicaSaved(function (Page $replica): void {
                        $replica->title = $replica->title.' (copy)';
                        $replica->status = PublishStatus::Draft;
                    }),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ...PublishActions::bulk(),
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [FaqsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPages::route('/'),
            'create' => CreatePage::route('/create'),
            'edit' => EditPage::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
