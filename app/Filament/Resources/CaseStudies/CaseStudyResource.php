<?php

namespace App\Filament\Resources\CaseStudies;

use App\Filament\RelationManagers\TechnologiesRelationManager;
use App\Filament\Resources\CaseStudies\Pages\CreateCaseStudy;
use App\Filament\Resources\CaseStudies\Pages\EditCaseStudy;
use App\Filament\Resources\CaseStudies\Pages\ListCaseStudies;
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
use App\Models\CaseStudy;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
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
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class CaseStudyResource extends Resource
{
    protected static ?string $model = CaseStudy::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'Work';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Case study')->tabs([
                Tab::make('Content')->schema([
                    Section::make()->description('Only genuine work. Leave the client empty for anonymised studies.')->schema([
                        SlugField::source('title', 'Title'),
                        SlugField::make()->helperText('Public URL: /case-studies/{slug}.'),
                        Select::make('client_id')->label('Client')->relationship('client', 'name')->searchable()->preload()->native(false),
                        Select::make('industry_id')->label('Industry')->relationship('industry', 'name')->searchable()->preload()->native(false),
                        Textarea::make('excerpt')->rows(2)->maxLength(400)->columnSpanFull(),
                        RichEditor::make('challenge')->required()->columnSpanFull(),
                        RichEditor::make('solution')->label('Approach and solution')->required()->columnSpanFull(),
                        RichEditor::make('implementation')->columnSpanFull(),
                        RichEditor::make('results')->columnSpanFull(),
                        Repeater::make('outcomes')->defaultItems(0)->label('Outcomes')->schema([
                            TextInput::make('label')->required()->maxLength(120),
                            TextInput::make('value')->required()->maxLength(80)->helperText('Shown exactly as entered.'),
                            Select::make('kind')->options(['qualitative' => 'Qualitative', 'quantitative' => 'Quantitative'])->default('qualitative')->required()->native(false),
                            TextInput::make('note')->maxLength(160),
                        ])->columns(2)->collapsible()->collapsed()->itemLabel(fn (array $state): ?string => $state['label'] ?? null)->maxItems(8)->columnSpanFull(),
                    ])->columns(2),
                    Section::make('Media')->schema([
                        MediaFields::image('hero', 'Hero image'),
                        MediaFields::gallery('gallery', 'Gallery'),
                    ]),
                ]),
                Tab::make('Relationships')->schema([
                    RelationSelect::many('services', 'Services delivered'),
                    RelationSelect::many('products', 'Products used'),
                    CtaSelect::make(),
                ]),
                Tab::make('Sections')->schema([BlockBuilder::make('case_study')]),
                Tab::make('Publishing')->schema([PublishingFields::make(CaseStudy::class), AuditSection::make()]),
                Tab::make('SEO')->schema([SeoFields::make()]),
            ])->columnSpanFull()->persistTabInQueryString(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('published_at', 'desc')
            ->columns([
                TextColumn::make('title')->searchable()->sortable(),
                TextColumn::make('client.name')->label('Client')->placeholder('Anonymised'),
                TextColumn::make('industry.name')->label('Industry')->placeholder('—'),
                Columns::status(),
                Columns::featured(),
                Columns::publishedAt(),
            ])
            ->filters([
                Columns::statusFilter(),
                SelectFilter::make('industry_id')->label('Industry')->relationship('industry', 'name')->preload(),
                TrashedFilter::make(),
            ])
            ->recordActions([EditAction::make(), ActionGroup::make([PreviewAction::make(), ...PublishActions::record()])])
            ->toolbarActions([BulkActionGroup::make([...PublishActions::bulk(), DeleteBulkAction::make(), RestoreBulkAction::make()])]);
    }

    public static function getRelations(): array
    {
        return [TechnologiesRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCaseStudies::route('/'),
            'create' => CreateCaseStudy::route('/create'),
            'edit' => EditCaseStudy::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
