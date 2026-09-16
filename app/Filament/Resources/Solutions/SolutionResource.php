<?php

namespace App\Filament\Resources\Solutions;

use App\Filament\RelationManagers\FaqsRelationManager;
use App\Filament\RelationManagers\TechnologiesRelationManager;
use App\Filament\Resources\Solutions\Pages\CreateSolution;
use App\Filament\Resources\Solutions\Pages\EditSolution;
use App\Filament\Resources\Solutions\Pages\ListSolutions;
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
use App\Models\Solution;
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

class SolutionResource extends Resource
{
    protected static ?string $model = Solution::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLightBulb;

    protected static string|UnitEnum|null $navigationGroup = 'Solutions';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Solution')->tabs([
                Tab::make('Content')->schema([
                    Section::make()->schema([
                        SlugField::source(),
                        SlugField::make()->helperText('Public URL: /solutions/{slug}.'),
                        TextInput::make('tagline')->maxLength(160)->columnSpanFull(),
                        Textarea::make('short_description')->label('Summary')->rows(2)->maxLength(400)->columnSpanFull(),
                        RichEditor::make('problem_statement')->label('The business problem')->columnSpanFull(),
                        RichEditor::make('approach')->label('How Markedge solves it')->columnSpanFull(),
                        Repeater::make('outcomes')->defaultItems(0)->label('Outcomes (qualitative statements, never invented numbers)')->schema([
                            TextInput::make('label')->required()->maxLength(120),
                            Textarea::make('text')->rows(2)->maxLength(300),
                        ])->collapsible()->collapsed()->itemLabel(fn (array $state): ?string => $state['label'] ?? null)->maxItems(8)->columnSpanFull(),
                        MediaFields::image('hero', 'Hero image')->columnSpanFull(),
                    ])->columns(2),
                ]),
                Tab::make('Relationships')->schema([
                    RelationSelect::many('services', 'Services that deliver this solution'),
                    RelationSelect::many('products', 'Products that accelerate it'),
                    RelationSelect::many('industries', 'Industries where it applies'),
                    CtaSelect::make(),
                ]),
                Tab::make('Sections')->schema([BlockBuilder::make('solution')]),
                Tab::make('Publishing')->schema([PublishingFields::make(Solution::class), AuditSection::make()]),
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
                TextColumn::make('services_count')->counts('services')->label('Services'),
                TextColumn::make('products_count')->counts('products')->label('Products'),
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
            'index' => ListSolutions::route('/'),
            'create' => CreateSolution::route('/create'),
            'edit' => EditSolution::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
