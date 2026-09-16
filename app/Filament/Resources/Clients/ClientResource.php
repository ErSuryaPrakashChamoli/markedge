<?php

namespace App\Filament\Resources\Clients;

use App\Filament\Resources\Clients\Pages\CreateClient;
use App\Filament\Resources\Clients\Pages\EditClient;
use App\Filament\Resources\Clients\Pages\ListClients;
use App\Filament\Support\MediaFields;
use App\Filament\Support\SlugField;
use App\Models\Client;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static string|UnitEnum|null $navigationGroup = 'Work';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->description('Only add clients Markedge is permitted to name. Clients stay hidden until you switch them on.')->schema([
                SlugField::source(),
                SlugField::make(),
                TextInput::make('website_url')->label('Website')->url()->maxLength(255),
                Select::make('industry_id')->label('Industry')->relationship('industry', 'name')->searchable()->preload()->native(false),
                Textarea::make('description')->rows(2)->maxLength(400)->columnSpanFull(),
                MediaFields::image('logo', 'Logo', 'PNG or WebP with transparency. SVG is not accepted.'),
                Toggle::make('is_visible')->label('Show on the website')->inline(false),
                Toggle::make('show_in_logo_cloud')->label('Include in logo cloud')->inline(false),
                TextInput::make('sort_order')->numeric()->default(0),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                SpatieMediaLibraryImageColumn::make('logo')->collection('logo')->conversion('thumb')->label('')->square()->size(32),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('industry.name')->label('Industry')->placeholder('—'),
                IconColumn::make('is_visible')->label('Visible')->boolean(),
                IconColumn::make('show_in_logo_cloud')->label('Logo cloud')->boolean(),
            ])
            ->filters([TernaryFilter::make('is_visible')->label('Visible'), TrashedFilter::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make(), RestoreBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListClients::route('/'),
            'create' => CreateClient::route('/create'),
            'edit' => EditClient::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
