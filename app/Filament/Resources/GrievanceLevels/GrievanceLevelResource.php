<?php

namespace App\Filament\Resources\GrievanceLevels;

use App\Filament\Resources\GrievanceLevels\Pages\ManageGrievanceLevels;
use App\Models\GrievanceLevel;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

/**
 * The grievance redressal hierarchy shown by the "Grievance redressal" page block. Row order is the escalation order.
 */
class GrievanceLevelResource extends Resource
{
    protected static ?string $model = GrievanceLevel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedScale;

    protected static string|UnitEnum|null $navigationGroup = 'Website';

    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Level name')->required()->maxLength(120)->placeholder('e.g. Grievance Officer'),
            TextInput::make('response_time')->label('Response time')->maxLength(80)->placeholder('e.g. Within 7 working days'),
            Textarea::make('description')->label('When to contact this level')->rows(3)->maxLength(600)->columnSpanFull()
                ->placeholder('e.g. If your complaint is not resolved by Customer Support within 48 hours.'),
            Toggle::make('is_visible')->label('Visible')->default(true),
            TextInput::make('sort_order')->numeric()->default(0)->helperText('Lower numbers come first. You can also drag rows in the list.'),
            Repeater::make('contacts')->label('Contact persons')->relationship()->orderColumn('sort_order')->schema([
                TextInput::make('name')->required()->maxLength(120),
                TextInput::make('designation')->maxLength(120),
                TextInput::make('email')->email()->maxLength(255),
                TextInput::make('phone')->tel()->maxLength(40),
                Textarea::make('address')->rows(2)->maxLength(500)->columnSpanFull(),
            ])->columns(2)->collapsible()->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                ->addActionLabel('Add contact person')->maxItems(10)->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('level')->label('Level')->rowIndex(),
                TextColumn::make('name')->searchable(),
                TextColumn::make('contacts_count')->counts('contacts')->label('Contacts'),
                TextColumn::make('response_time')->label('Response time')->placeholder('—'),
                IconColumn::make('is_visible')->label('Visible')->boolean(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageGrievanceLevels::route('/')];
    }
}
