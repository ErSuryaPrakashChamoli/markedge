<?php

namespace App\Filament\Resources\LandingPages;

use App\Enums\PublishStatus;
use App\Filament\RelationManagers\FaqsRelationManager;
use App\Filament\Resources\LandingPages\Pages\CreateLandingPage;
use App\Filament\Resources\LandingPages\Pages\EditLandingPage;
use App\Filament\Resources\LandingPages\Pages\ListLandingPages;
use App\Filament\Support\AuditSection;
use App\Filament\Support\BlockBuilder;
use App\Filament\Support\Columns;
use App\Filament\Support\CtaSelect;
use App\Filament\Support\PreviewAction;
use App\Filament\Support\PublishActions;
use App\Filament\Support\PublishingFields;
use App\Filament\Support\SeoFields;
use App\Filament\Support\SlugField;
use App\Models\LandingPage;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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

class LandingPageResource extends Resource
{
    protected static ?string $model = LandingPage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFlag;

    protected static string|UnitEnum|null $navigationGroup = 'Marketing';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Landing page')->tabs([
                Tab::make('Setup')->icon(Heroicon::OutlinedFlag)->schema([
                    Section::make()->schema([
                        SlugField::source('title', 'Title'),
                        SlugField::make()->helperText('Public URL: /lp/{slug}.'),
                        Select::make('campaign_id')->label('Campaign')->relationship('campaign', 'name')->searchable()->preload()->native(false),
                        Select::make('form_id')->label('Form')->relationship('form', 'name')->searchable()->preload()->native(false)->helperText('Used by lead form sections that do not choose their own form.'),
                        CtaSelect::make(),
                        Toggle::make('hide_navigation')->label('Hide main navigation')->default(true)->inline(false),
                        Toggle::make('hide_footer_links')->label('Reduce footer to legal links')->default(true)->inline(false),
                        KeyValue::make('tracking')->label('Extra dataLayer variables')->keyLabel('Variable')->valueLabel('Value')->helperText('Pushed to GTM on page load. No scripts.')->columnSpanFull(),
                    ])->columns(2),
                ]),
                Tab::make('Sections')->icon(Heroicon::OutlinedSquares2x2)->schema([BlockBuilder::make('landing_page')]),
                Tab::make('Publishing')->icon(Heroicon::OutlinedPaperAirplane)->schema([
                    PublishingFields::make(LandingPage::class, featured: false, sortOrder: false),
                    Section::make('Expiry')->schema([
                        DateTimePicker::make('expires_at')->native(false)->seconds(false)->helperText('After this date visitors are redirected instead of seeing a 404.'),
                        TextInput::make('expired_redirect_url')->label('Redirect after expiry')->default('/')->maxLength(500)->rules(['nullable', 'regex:#^(/[^\s]*|https?://[^\s]+)$#']),
                    ])->columns(2),
                    AuditSection::make(),
                ]),
                Tab::make('SEO')->icon(Heroicon::OutlinedMagnifyingGlass)->schema([SeoFields::make()]),
            ])->columnSpanFull()->persistTabInQueryString(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('title')->searchable()->sortable()->description(fn (LandingPage $record): string => '/lp/'.$record->slug),
                TextColumn::make('campaign.name')->label('Campaign')->placeholder('—'),
                TextColumn::make('form.name')->label('Form')->placeholder('—')->toggleable(),
                TextColumn::make('leads_count')->counts('leads')->label('Leads'),
                Columns::status(),
                TextColumn::make('expires_at')->dateTime('d M Y')->placeholder('—')->toggleable(),
                Columns::updatedAt(),
            ])
            ->filters([
                Columns::statusFilter(),
                SelectFilter::make('campaign_id')->label('Campaign')->relationship('campaign', 'name')->preload(),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                ActionGroup::make([
                    PreviewAction::make(),
                    ...PublishActions::record(),
                    ReplicateAction::make()->excludeAttributes(['slug', 'published_at'])->beforeReplicaSaved(function (LandingPage $replica): void {
                        $replica->title .= ' (copy)';
                        $replica->status = PublishStatus::Draft;
                    }),
                ]),
            ])
            ->toolbarActions([BulkActionGroup::make([...PublishActions::bulk(), DeleteBulkAction::make(), RestoreBulkAction::make()])]);
    }

    public static function getRelations(): array
    {
        return [FaqsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLandingPages::route('/'),
            'create' => CreateLandingPage::route('/create'),
            'edit' => EditLandingPage::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
