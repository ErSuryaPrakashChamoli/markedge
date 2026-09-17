<?php

namespace App\Filament\Resources\ApiKeys;

use App\Filament\Resources\ApiKeys\Pages\ListApiKeys;
use App\Models\ApiKey;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use UnitEnum;

/**
 * API credentials. The plain key is shown once in the creation notification and never stored.
 */
class ApiKeyResource extends Resource
{
    protected static ?string $model = ApiKey::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'API keys';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(120)->helperText('Who or what uses this key, e.g. "CRM sync".'),
            CheckboxList::make('abilities')->options(array_combine(ApiKey::ABILITIES, ApiKey::ABILITIES))->required()->columns(2),
            DateTimePicker::make('expires_at')->native(false)->seconds(false)->helperText('Optional expiry.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('prefix')->label('Key prefix')->fontFamily('mono'),
                TextColumn::make('abilities')->badge()->separator(','),
                IconColumn::make('is_active')->label('Active')->boolean(),
                TextColumn::make('last_used_at')->dateTime('d M Y H:i')->placeholder('never'),
                TextColumn::make('expires_at')->dateTime('d M Y H:i')->placeholder('—'),
            ])
            ->headerActions([
                CreateAction::make()->label('Issue key')->using(function (array $data): Model {
                    $issued = ApiKey::issue($data['name'], $data['abilities'] ?? [], auth()->id(), filled($data['expires_at'] ?? null) ? Carbon::parse($data['expires_at']) : null);

                    Notification::make()->title('API key issued. Copy it now; it will not be shown again.')->body($issued['plain'])->success()->persistent()->send();

                    return $issued['key'];
                })->successNotification(null),
            ])
            ->recordActions([
                Action::make('revoke')->label('Revoke')->icon(Heroicon::OutlinedNoSymbol)->color('danger')->requiresConfirmation()
                    ->visible(fn (ApiKey $record): bool => $record->is_active)
                    ->action(function (ApiKey $record): void {
                        $record->update(['is_active' => false]);
                        Notification::make()->title('Key revoked.')->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListApiKeys::route('/')];
    }
}
