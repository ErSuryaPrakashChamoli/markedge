<?php

namespace App\Filament\Resources\Roles;

use App\Filament\Resources\Roles\Pages\CreateRole;
use App\Filament\Resources\Roles\Pages\EditRole;
use App\Filament\Resources\Roles\Pages\ListRoles;
use App\Models\User;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use UnitEnum;

/**
 * Roles are sets of "{subject}.{action}" permissions. The permission list itself comes from
 * config/markedge.php through the seeder; nothing is defined twice.
 */
class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(60)
                    ->unique(ignoreRecord: true)
                    ->disabled(fn (?Role $record): bool => $record?->name === User::SUPER_ADMIN_ROLE),
            ]),
            ...static::permissionSections(),
        ]);
    }

    /**
     * One collapsible section per subject so 300+ permissions stay scannable.
     *
     * @return array<int, Section>
     */
    protected static function permissionSections(): array
    {
        $subjects = config('markedge.permissions.subjects', []);
        $actions = config('markedge.permissions.actions', []);

        return [
            Section::make('Permissions')
                ->description('Super Admin always has every permission through the gate and cannot be edited here.')
                ->schema([
                    CheckboxList::make('permissions')
                        ->relationship('permissions', 'name')
                        ->options(fn (): array => Permission::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->bulkToggleable()
                        ->columns(4)
                        ->disabled(fn (?Role $record): bool => $record?->name === User::SUPER_ADMIN_ROLE)
                        ->helperText(count($subjects).' subjects × '.count($actions).' actions. Use the search box to filter, e.g. "articles." or ".publish".'),
                ]),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('permissions_count')->counts('permissions')->label('Permissions'),
                TextColumn::make('users_count')->counts('users')->label('Users'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->hidden(fn (Role $record): bool => $record->name === User::SUPER_ADMIN_ROLE || $record->users()->exists()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRoles::route('/'),
            'create' => CreateRole::route('/create'),
            'edit' => EditRole::route('/{record}/edit'),
        ];
    }
}
