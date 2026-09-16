<?php

namespace App\Filament\Resources\Services;

use App\Enums\PublishStatus;
use App\Filament\RelationManagers\FaqsRelationManager;
use App\Filament\RelationManagers\TechnologiesRelationManager;
use App\Filament\Resources\Services\Pages\CreateService;
use App\Filament\Resources\Services\Pages\EditService;
use App\Filament\Resources\Services\Pages\ListServices;
use App\Filament\Support\AuditSection;
use App\Filament\Support\BlockBuilder;
use App\Filament\Support\Columns;
use App\Filament\Support\CtaSelect;
use App\Filament\Support\MediaFields;
use App\Filament\Support\PreviewAction;
use App\Filament\Support\PublishActions;
use App\Filament\Support\PublishingFields;
use App\Filament\Support\RelationSelect;
use App\Filament\Support\SeoFields;
use App\Filament\Support\SlugField;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Rules\UniqueSlugAcross;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Repeater;
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
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static string|UnitEnum|null $navigationGroup = 'Services';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Service')->tabs([
                Tab::make('Content')->icon(Heroicon::OutlinedDocumentText)->schema([
                    Section::make()->schema([
                        Select::make('service_category_id')->label('Category')->relationship('category', 'name')->required()->native(false)->preload(),
                        SlugField::source(),
                        SlugField::make(extraRules: [fn (?Model $record) => new UniqueSlugAcross([ServiceCategory::class], $record)])->helperText('Public URL: /services/{slug}.'),
                        TextInput::make('tagline')->maxLength(160),
                        Textarea::make('short_description')->rows(2)->maxLength(400)->columnSpanFull(),
                        RichEditor::make('overview')->columnSpanFull(),
                        MediaFields::image('hero', 'Hero image')->columnSpanFull(),
                    ])->columns(2),
                ]),
                Tab::make('Details')->icon(Heroicon::OutlinedListBullet)->schema([
                    static::repeater('benefits', 'Benefits', withIcon: true),
                    static::repeater('features', 'Features'),
                    Repeater::make('process')->defaultItems(0)->label('Process steps')->schema([
                        TextInput::make('title')->required()->maxLength(80),
                        Textarea::make('text')->rows(2)->maxLength(400),
                    ])->collapsible()->collapsed()->itemLabel(fn (array $state): ?string => $state['title'] ?? null)->maxItems(10)->columnSpanFull(),
                    Repeater::make('deliverables')->defaultItems(0)->simple(TextInput::make('text')->required()->maxLength(160))->maxItems(12)->columnSpanFull(),
                ]),
                Tab::make('Relationships')->icon(Heroicon::OutlinedLink)->schema([
                    RelationSelect::many('relatedServices', 'Related services'),
                    RelationSelect::many('products', 'Products that support this service'),
                    RelationSelect::many('solutions', 'Solutions'),
                    RelationSelect::many('industries', 'Industries'),
                    CtaSelect::make(),
                ]),
                Tab::make('Sections')->icon(Heroicon::OutlinedSquares2x2)->schema([BlockBuilder::make('service')]),
                Tab::make('Publishing')->icon(Heroicon::OutlinedPaperAirplane)->schema([PublishingFields::make(Service::class), AuditSection::make()]),
                Tab::make('SEO')->icon(Heroicon::OutlinedMagnifyingGlass)->schema([SeoFields::make()]),
            ])->columnSpanFull()->persistTabInQueryString(),
        ]);
    }

    protected static function repeater(string $name, string $label, bool $withIcon = false): Repeater
    {
        $fields = [
            TextInput::make('title')->required()->maxLength(120),
            Textarea::make('text')->rows(2)->maxLength(400),
        ];

        if ($withIcon) {
            $fields[] = TextInput::make('icon')->maxLength(60)->helperText('Optional Heroicon name.');
        }

        return Repeater::make($name)->defaultItems(0)->label($label)->schema($fields)->collapsible()->collapsed()
            ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)->maxItems(12)->columnSpanFull();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('name')->searchable()->sortable()->description(fn (Service $record): string => '/services/'.$record->slug),
                TextColumn::make('category.name')->label('Category')->sortable()->badge()->color('gray'),
                Columns::status(),
                Columns::featured(),
                Columns::sortOrder(),
                Columns::publishedAt(),
                Columns::updatedAt(),
            ])
            ->filters([
                SelectFilter::make('service_category_id')->label('Category')->relationship('category', 'name')->preload(),
                Columns::statusFilter(),
                TernaryFilter::make('is_featured')->label('Featured'),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                ActionGroup::make([
                    PreviewAction::make(),
                    ...PublishActions::record(),
                    ReplicateAction::make()->excludeAttributes(['slug', 'published_at'])->beforeReplicaSaved(function (Service $replica): void {
                        $replica->name .= ' (copy)';
                        $replica->status = PublishStatus::Draft;
                    }),
                ]),
            ])
            ->toolbarActions([BulkActionGroup::make([...PublishActions::bulk(), DeleteBulkAction::make(), RestoreBulkAction::make()])]);
    }

    public static function getRelations(): array
    {
        return [FaqsRelationManager::class, TechnologiesRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListServices::route('/'),
            'create' => CreateService::route('/create'),
            'edit' => EditService::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
