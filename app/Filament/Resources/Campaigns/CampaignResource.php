<?php

namespace App\Filament\Resources\Campaigns;

use App\Enums\CampaignChannel;
use App\Enums\CampaignStatus;
use App\Filament\Resources\Campaigns\Pages\CreateCampaign;
use App\Filament\Resources\Campaigns\Pages\EditCampaign;
use App\Filament\Resources\Campaigns\Pages\ListCampaigns;
use App\Filament\Support\SlugField;
use App\Models\Campaign;
use App\Services\Cms\CampaignTargeting;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class CampaignResource extends Resource
{
    protected static ?string $model = Campaign::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static string|UnitEnum|null $navigationGroup = 'Marketing';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Campaign')->schema([
                SlugField::source(),
                SlugField::make()->label('Internal code'),
                Select::make('channel')->options(CampaignChannel::class)->default(CampaignChannel::Other)->required()->native(false),
                Select::make('status')->options(CampaignStatus::class)->default(CampaignStatus::Planned)->required()->native(false),
                DateTimePicker::make('starts_at')->native(false)->seconds(false),
                DateTimePicker::make('ends_at')->native(false)->seconds(false)->after('starts_at'),
            ])->columns(2),
            Section::make('Tracking parameters')->description('Leads are attributed by matching utm_campaign (case-insensitive).')->schema([
                TextInput::make('utm_campaign')->required()->maxLength(120)->unique(ignoreRecord: true)->regex('/^[A-Za-z0-9_\-\.]+$/')->live(onBlur: true),
                TextInput::make('utm_source')->maxLength(80)->live(onBlur: true),
                TextInput::make('utm_medium')->maxLength(80)->live(onBlur: true),
                TextInput::make('utm_term')->maxLength(120),
                TextInput::make('utm_content')->maxLength(120),
                Placeholder::make('example_url')->label('Example URL')->content(function (Get $get): string {
                    $path = $get('landing_page_id') ? '/lp/{landing-page}' : '/';
                    $query = http_build_query(array_filter([
                        'utm_source' => $get('utm_source'), 'utm_medium' => $get('utm_medium'), 'utm_campaign' => $get('utm_campaign'),
                    ]));

                    return url($path).($query ? '?'.$query : '');
                })->columnSpanFull(),
            ])->columns(2),
            Section::make('Defaults')->schema([
                Select::make('landing_page_id')->label('Default landing page')->relationship('defaultLandingPage', 'title')->searchable()->preload()->native(false)->live(),
                Select::make('form_id')->label('Default form')->relationship('form', 'name')->searchable()->preload()->native(false),
                Select::make('cta_id')->label('Default CTA')->relationship('cta', 'name')->searchable()->preload()->native(false)->live(),
                Toggle::make('personalize_cta')->label('Show this CTA to campaign visitors')->inline(false)->live()
                    ->helperText('Deterministic rule: while the campaign is running, visitors whose last touch is this utm_campaign see the campaign CTA instead of the page default. Search engines, previews and everyone else see the default.'),
                Placeholder::make('targeting_rule')->label('Targeting rule')->content(fn (?Campaign $record): string => $record ? CampaignTargeting::explain($record) : 'Save the campaign to see the rule.')->columnSpanFull(),
                KeyValue::make('tracking')->label('Conversion labels')->keyLabel('Platform')->valueLabel('Label / ID')->columnSpanFull(),
                Textarea::make('notes')->rows(3)->columnSpanFull(),
            ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')->searchable()->sortable()->description(fn (Campaign $record): string => $record->utm_campaign),
                TextColumn::make('channel')->badge()->color('gray'),
                TextColumn::make('status')->badge(),
                TextColumn::make('leads_count')->counts('leads')->label('Leads')->sortable(),
                TextColumn::make('starts_at')->date('d M Y')->placeholder('—'),
                TextColumn::make('ends_at')->date('d M Y')->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('status')->options(CampaignStatus::class),
                SelectFilter::make('channel')->options(CampaignChannel::class),
                TrashedFilter::make(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make(), RestoreBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCampaigns::route('/'),
            'create' => CreateCampaign::route('/create'),
            'edit' => EditCampaign::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
