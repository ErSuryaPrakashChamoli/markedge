<?php

namespace App\Filament\Resources\Technologies;

use App\Enums\TechnologyCategory;
use App\Filament\Resources\Technologies\Pages\CreateTechnology;
use App\Filament\Resources\Technologies\Pages\EditTechnology;
use App\Filament\Resources\Technologies\Pages\ListTechnologies;
use App\Filament\Support\MediaFields;
use App\Filament\Support\SlugField;
use App\Models\Technology;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class TechnologyResource extends Resource
{
    protected static ?string $model = Technology::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCpuChip;

    protected static string|UnitEnum|null $navigationGroup = 'Services';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->schema([
                SlugField::source(),
                SlugField::make(),
                Select::make('category')->options(TechnologyCategory::class)->required()->native(false),
                TextInput::make('website_url')->label('Website')->url()->maxLength(255),
                Textarea::make('description')->rows(2)->maxLength(400)->columnSpanFull(),
                MediaFields::image('logo', 'Logo', 'PNG or WebP with transparency works best. SVG is not accepted.'),
                Toggle::make('is_visible')->label('Visible')->default(true)->inline(false),
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
                TextColumn::make('category')->badge()->sortable(),
                IconColumn::make('is_visible')->label('Visible')->boolean(),
            ])
            ->filters([SelectFilter::make('category')->options(TechnologyCategory::class)])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTechnologies::route('/'),
            'create' => CreateTechnology::route('/create'),
            'edit' => EditTechnology::route('/{record}/edit'),
        ];
    }
}
