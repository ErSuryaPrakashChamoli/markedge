<?php

namespace App\Filament\Resources\ServiceCategories;

use App\Filament\RelationManagers\FaqsRelationManager;
use App\Filament\Resources\ServiceCategories\Pages\CreateServiceCategory;
use App\Filament\Resources\ServiceCategories\Pages\EditServiceCategory;
use App\Filament\Resources\ServiceCategories\Pages\ListServiceCategories;
use App\Filament\Support\AuditSection;
use App\Filament\Support\BlockBuilder;
use App\Filament\Support\Columns;
use App\Filament\Support\CtaSelect;
use App\Filament\Support\MediaFields;
use App\Filament\Support\PreviewAction;
use App\Filament\Support\PublishActions;
use App\Filament\Support\PublishingFields;
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
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class ServiceCategoryResource extends Resource
{
    protected static ?string $model = ServiceCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Services';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Category')->tabs([
                Tab::make('Content')->schema([
                    Section::make()->schema([
                        SlugField::source(),
                        SlugField::make(extraRules: [fn (?Model $record) => new UniqueSlugAcross([Service::class], $record)])->helperText('Public URL: /services/{slug}. Shared with service slugs.'),
                        TextInput::make('pillar_label')->label('Pillar label')->maxLength(20)->helperText('e.g. BUILD, OPERATE, GROW'),
                        TextInput::make('tagline')->maxLength(160),
                        Textarea::make('short_description')->rows(2)->maxLength(400)->columnSpanFull(),
                        RichEditor::make('description')->columnSpanFull(),
                        TextInput::make('icon')->maxLength(60)->helperText('Optional Heroicon name.'),
                        CtaSelect::make(),
                        MediaFields::image('hero', 'Hero image')->columnSpanFull(),
                    ])->columns(2),
                ]),
                Tab::make('Sections')->schema([BlockBuilder::make('service_category')]),
                Tab::make('Publishing')->schema([PublishingFields::make(ServiceCategory::class), AuditSection::make()]),
                Tab::make('SEO')->schema([SeoFields::make()]),
            ])->columnSpanFull()->persistTabInQueryString(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('pillar_label')->badge()->color('gray'),
                TextColumn::make('services_count')->counts('services')->label('Services'),
                Columns::status(),
                Columns::featured(),
                Columns::updatedAt(),
            ])
            ->filters([Columns::statusFilter(), TrashedFilter::make()])
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
            'index' => ListServiceCategories::route('/'),
            'create' => CreateServiceCategory::route('/create'),
            'edit' => EditServiceCategory::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
