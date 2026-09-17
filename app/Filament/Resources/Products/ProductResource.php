<?php

namespace App\Filament\Resources\Products;

use App\Enums\ProductStatus;
use App\Filament\RelationManagers\FaqsRelationManager;
use App\Filament\RelationManagers\TechnologiesRelationManager;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Products\RelationManagers\DocumentsRelationManager;
use App\Filament\Resources\Products\RelationManagers\FeaturesRelationManager;
use App\Filament\Resources\Products\RelationManagers\ModulesRelationManager;
use App\Filament\Support\AuditSection;
use App\Filament\Support\BlockBuilder;
use App\Filament\Support\Columns;
use App\Filament\Support\CtaSelect;
use App\Filament\Support\MediaFields;
use App\Filament\Support\PreviewAction;
use App\Filament\Support\RelationSelect;
use App\Filament\Support\SeoFields;
use App\Filament\Support\SlugField;
use App\Models\Product;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Gate;
use UnitEnum;

/**
 * Generic product resource. Every product, current or future, is a row here.
 */
class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static string|UnitEnum|null $navigationGroup = 'Products';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Product')->tabs([
                Tab::make('Content')->icon(Heroicon::OutlinedDocumentText)->schema([
                    Section::make()->schema([
                        SlugField::source(),
                        SlugField::make()->helperText('Public URL: /products/{slug}.'),
                        TextInput::make('tagline')->maxLength(160),
                        TextInput::make('product_type')->label('Product type')->maxLength(60)->helperText('e.g. Business platform, SaaS'),
                        Textarea::make('short_description')->rows(2)->maxLength(400)->columnSpanFull(),
                        RichEditor::make('long_description')->columnSpanFull(),
                    ])->columns(2),
                    Section::make('Brand and hero')->schema([
                        MediaFields::image('logo', 'Product logo'),
                        MediaFields::image('hero', 'Hero image or UI mockup'),
                    ])->columns(2),
                ]),
                Tab::make('Benefits and use cases')->icon(Heroicon::OutlinedListBullet)->schema([
                    Repeater::make('benefits')->defaultItems(0)->schema([
                        TextInput::make('title')->required()->maxLength(120),
                        Textarea::make('text')->rows(2)->maxLength(400),
                        TextInput::make('icon')->maxLength(60)->helperText('Optional Heroicon name.'),
                    ])->collapsible()->collapsed()->itemLabel(fn (array $state): ?string => $state['title'] ?? null)->maxItems(12)->columnSpanFull(),
                    Repeater::make('use_cases')->defaultItems(0)->label('Use cases')->schema([
                        TextInput::make('title')->required()->maxLength(120),
                        Textarea::make('text')->rows(2)->maxLength(400),
                    ])->collapsible()->collapsed()->itemLabel(fn (array $state): ?string => $state['title'] ?? null)->maxItems(12)->columnSpanFull(),
                    Repeater::make('integrations')->defaultItems(0)->schema([
                        TextInput::make('name')->required()->maxLength(80),
                        TextInput::make('url')->url()->maxLength(255),
                    ])->collapsible()->collapsed()->itemLabel(fn (array $state): ?string => $state['name'] ?? null)->maxItems(24)->columns(2)->columnSpanFull(),
                ]),
                Tab::make('Deployment and security')->icon(Heroicon::OutlinedShieldCheck)->schema([
                    Repeater::make('deployment')->label('Deployment options')->defaultItems(0)->schema([
                        TextInput::make('label')->required()->maxLength(80),
                        Textarea::make('text')->rows(2)->maxLength(400),
                    ])->collapsible()->collapsed()->itemLabel(fn (array $state): ?string => $state['label'] ?? null)->maxItems(8)->columnSpanFull()
                        ->helperText('State only deployment options that exist today. Leave empty rather than promise.'),
                    Repeater::make('security')->label('Security statements')->defaultItems(0)->schema([
                        TextInput::make('label')->required()->maxLength(80),
                        Textarea::make('text')->rows(2)->maxLength(400),
                    ])->collapsible()->collapsed()->itemLabel(fn (array $state): ?string => $state['label'] ?? null)->maxItems(12)->columnSpanFull()
                        ->helperText('Factual, verifiable statements only. Certifications or audits must not be listed unless they are held.'),
                ]),
                Tab::make('Media')->icon(Heroicon::OutlinedPhoto)->schema([
                    MediaFields::gallery('screenshots', 'Screenshots'),
                    MediaFields::gallery('gallery', 'Gallery'),
                ]),
                Tab::make('Sections')->icon(Heroicon::OutlinedSquares2x2)->schema([BlockBuilder::make('product')]),
                Tab::make('Relationships')->icon(Heroicon::OutlinedLink)->schema([
                    RelationSelect::many('industries', 'Industries'),
                    RelationSelect::many('services', 'Related services'),
                    RelationSelect::many('solutions', 'Solutions'),
                    RelationSelect::many('caseStudies', 'Case studies', 'title'),
                ]),
                Tab::make('Demo and CTA')->icon(Heroicon::OutlinedCursorArrowRays)->schema([
                    Select::make('demo_form_id')->label('Demo form')->relationship('demoForm', 'name')->searchable()->preload()->native(false),
                    CtaSelect::make(),
                    TextInput::make('external_url')->label('External product URL')->url()->maxLength(255),
                ])->columns(2),
                Tab::make('Publishing')->icon(Heroicon::OutlinedPaperAirplane)->schema([
                    Section::make('Status')->schema([
                        Select::make('status')
                            ->options(fn (?Model $record): array => static::statusOptions($record))
                            ->default(ProductStatus::Draft)
                            ->required()
                            ->native(false)
                            ->helperText('Active and Coming soon products are visible on the website.'),
                        DateTimePicker::make('published_at')->label('Publish date')->native(false)->seconds(false),
                        DatePicker::make('launched_at')->label('Launch date')->native(false),
                        Toggle::make('is_featured')->label('Featured')->inline(false),
                        TextInput::make('sort_order')->label('Display order')->numeric()->default(0),
                    ])->columns(2),
                    AuditSection::make(),
                ]),
                Tab::make('SEO')->icon(Heroicon::OutlinedMagnifyingGlass)->schema([SeoFields::make()]),
            ])->columnSpanFull()->persistTabInQueryString(),
        ]);
    }

    /**
     * @return array<string, string>
     */
    public static function statusOptions(?Product $record): array
    {
        $canPublish = Gate::allows('publish', $record ?? Product::class);

        return collect(ProductStatus::cases())
            ->filter(fn (ProductStatus $status): bool => $canPublish || $status === ProductStatus::Draft)
            ->mapWithKeys(fn (ProductStatus $status): array => [$status->value => $status->getLabel()])
            ->all();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withCount([
                'pageViews as views_30d' => fn (Builder $q) => $q->where('created_at', '>=', now()->subDays(30)),
                'leads as leads_30d' => fn (Builder $q) => $q->where('created_at', '>=', now()->subDays(30)),
            ]))
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                SpatieMediaLibraryImageColumn::make('logo')->collection('logo')->conversion('thumb')->label('')->square()->size(36),
                TextColumn::make('name')->searchable()->sortable()->description(fn (Product $record): string => '/products/'.$record->slug),
                TextColumn::make('product_type')->label('Type')->badge()->color('gray')->placeholder('—'),
                Columns::status(),
                Columns::featured(),
                TextColumn::make('features_count')->counts('features')->label('Features'),
                TextColumn::make('views_30d')->label('Views (30d)')->numeric()->sortable()->toggleable(),
                TextColumn::make('leads_30d')->label('Leads (30d)')->numeric()->sortable()->toggleable(),
                Columns::updatedAt(),
            ])
            ->filters([SelectFilter::make('status')->options(ProductStatus::class)->multiple(), TrashedFilter::make()])
            ->recordActions([EditAction::make(), ActionGroup::make([PreviewAction::make()])])
            ->toolbarActions([
                BulkActionGroup::make([
                    static::statusBulkAction('activateSelected', 'Set active', ProductStatus::Active, Heroicon::OutlinedCheckCircle, 'success'),
                    static::statusBulkAction('archiveSelected', 'Archive', ProductStatus::Archived, Heroicon::OutlinedArchiveBox, 'danger'),
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    protected static function statusBulkAction(string $name, string $label, ProductStatus $status, Heroicon $icon, string $color): BulkAction
    {
        return BulkAction::make($name)
            ->label($label)
            ->icon($icon)
            ->color($color)
            ->requiresConfirmation()
            ->deselectRecordsAfterCompletion()
            ->action(function (Collection $records) use ($status): void {
                $changed = 0;

                foreach ($records as $record) {
                    if (Gate::allows('publish', $record)) {
                        $record->forceFill(['status' => $status, 'published_at' => $record->published_at ?? now()])->save();
                        $changed++;
                    }
                }

                Notification::make()->title("{$changed} product(s) updated.")->success()->send();
            });
    }

    public static function getRelations(): array
    {
        return [ModulesRelationManager::class, FeaturesRelationManager::class, DocumentsRelationManager::class, FaqsRelationManager::class, TechnologiesRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
