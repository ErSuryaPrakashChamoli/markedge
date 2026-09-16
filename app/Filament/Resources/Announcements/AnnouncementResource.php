<?php

namespace App\Filament\Resources\Announcements;

use App\Enums\AnnouncementDisplay;
use App\Filament\Resources\Announcements\Pages\ManageAnnouncements;
use App\Models\Announcement;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

class AnnouncementResource extends Resource
{
    protected static ?string $model = Announcement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static string|UnitEnum|null $navigationGroup = 'Website';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Textarea::make('message')->required()->rows(2)->maxLength(300)->columnSpanFull(),
            TextInput::make('link_label')->maxLength(60),
            TextInput::make('link_url')->label('Link URL')->maxLength(500)->rules(['nullable', 'regex:#^(/[^\s]*|https?://[^\s]+)$#']),
            Select::make('display')->options(AnnouncementDisplay::class)->default(AnnouncementDisplay::Bar)->required()->native(false),
            Select::make('style')->options(['dark' => 'Dark', 'brand' => 'Orange'])->default('dark')->native(false),
            DateTimePicker::make('starts_at')->native(false)->seconds(false),
            DateTimePicker::make('ends_at')->native(false)->seconds(false)->after('starts_at'),
            Toggle::make('is_active')->label('Active'),
            Toggle::make('is_dismissible')->label('Visitors can dismiss')->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('message')->limit(70)->searchable(),
                TextColumn::make('display')->badge(),
                IconColumn::make('is_active')->label('Active')->boolean(),
                TextColumn::make('starts_at')->dateTime('d M Y H:i')->placeholder('—'),
                TextColumn::make('ends_at')->dateTime('d M Y H:i')->placeholder('—'),
            ])
            ->filters([TernaryFilter::make('is_active')])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageAnnouncements::route('/')];
    }
}
