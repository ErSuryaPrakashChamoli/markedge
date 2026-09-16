<?php

namespace App\Filament\Resources\Industries;

use App\Filament\RelationManagers\FaqsRelationManager;
use App\Filament\RelationManagers\TechnologiesRelationManager;
use App\Filament\Resources\Industries\Pages\CreateIndustry;
use App\Filament\Resources\Industries\Pages\EditIndustry;
use App\Filament\Resources\Industries\Pages\ListIndustries;
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
use App\Models\Industry;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Repeater;
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
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class IndustryResource extends Resource
{
    protected static ?string $model = Industry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static string|UnitEnum|null $navigationGroup = 'Industries';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Industry')->tabs([
                Tab::make('Content')->schema([
                    Section::make()->description('Describe the sector and its technology challenges. Do not claim experience that has not been confirmed.')->schema([
                        SlugField::source(),
                        SlugField::make()->helperText('Public URL: /industries/{slug}.'),
                        TextInput::make('tagline')->maxLength(160)->columnSpanFull(),
                        Textarea::make('short_description')->rows(2)->maxLength(400)->columnSpanFull(),
                        RichEditor::make('description')->columnSpanFull(),
                        Repeater::make('challenges')->defaultItems(0)->label('Business challenges')->schema([
                            TextInput::make('title')->required()->maxLength(120),
                            Textarea::make('text')->rows(2)->maxLength(400),
                        ])->collapsible()->collapsed()->itemLabel(fn (array $state): ?string => $state['title'] ?? null)->maxItems(8)->columnSpanFull(),
                        MediaFields::image('hero', 'Hero image')->columnSpanFull(),
                    ])->columns(2),
                ]),
                Tab::make('Relationships')->schema([
                    RelationSelect::many('solutions', 'Solutions'),
                    RelationSelect::many('services', 'Services'),
                    RelationSelect::many('products', 'Products'),
                    CtaSelect::make(),
                ]),
                Tab::make('Sections')->schema([BlockBuilder::make('industry')]),
                Tab::make('Publishing')->schema([PublishingFields::make(Industry::class), AuditSection::make()]),
                Tab::make('SEO')->schema([SeoFields::make()]),
            ])->columnSpanFull()->persistTabInQueryString(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('case_studies_count')->counts('caseStudies')->label('Case studies'),
                Columns::status(),
                Columns::featured(),
                Columns::sortOrder(),
                Columns::updatedAt(),
            ])
            ->filters([Columns::statusFilter(), TernaryFilter::make('is_featured')->label('Featured'), TrashedFilter::make()])
            ->recordActions([EditAction::make(), ActionGroup::make([PreviewAction::make(), ...PublishActions::record()])])
            ->toolbarActions([BulkActionGroup::make([...PublishActions::bulk(), DeleteBulkAction::make(), RestoreBulkAction::make()])]);
    }

    public static function getRelations(): array
    {
        return [FaqsRelationManager::class, TechnologiesRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListIndustries::route('/'),
            'create' => CreateIndustry::route('/create'),
            'edit' => EditIndustry::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
