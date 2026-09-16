<?php

namespace App\Filament\Resources\Ctas;

use App\Enums\CtaAction;
use App\Enums\CtaVariant;
use App\Filament\Resources\Ctas\Pages\ManageCtas;
use App\Models\Cta;
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
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class CtaResource extends Resource
{
    protected static ?string $model = Cta::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCursorArrowRays;

    protected static string|UnitEnum|null $navigationGroup = 'Marketing';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'CTAs';

    protected static ?string $modelLabel = 'CTA';

    protected static ?string $pluralModelLabel = 'CTAs';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        $needsValue = fn (Get $get, string $field): bool => in_array($get($field), ['url', 'route', 'form', CtaAction::Url, CtaAction::Route, CtaAction::Form], true);
        $usesWhatsapp = fn (Get $get): bool => in_array('whatsapp', [static::value($get('primary_action')), static::value($get('secondary_action'))], true);

        return $schema->components([
            Section::make()->schema([
                TextInput::make('name')->label('Internal name')->required()->maxLength(80),
                TextInput::make('key')->required()->maxLength(60)->regex('/^[a-z0-9-]+$/')->unique(ignoreRecord: true)->helperText('Referenced by Global Settings, e.g. start-conversation.'),
                TextInput::make('headline')->maxLength(160)->columnSpanFull(),
                Textarea::make('body')->rows(2)->maxLength(400)->columnSpanFull(),
                Select::make('variant')->options(CtaVariant::class)->default(CtaVariant::Band)->required()->native(false),
                Toggle::make('is_active')->label('Active')->default(true)->inline(false),
            ])->columns(2),
            Section::make('Primary button')->schema([
                TextInput::make('primary_label')->required()->maxLength(60),
                Select::make('primary_action')->options(CtaAction::class)->default(CtaAction::Url)->required()->native(false)->live(),
                TextInput::make('primary_value')->label('Link')->maxLength(500)->visible(fn (Get $get): bool => $needsValue($get, 'primary_action'))
                    ->rules(['nullable', 'regex:#^(/[^\s]*|https?://[^\s]+|[a-z0-9\.\-]+)$#'])->helperText('A site path, full URL or named route.'),
            ])->columns(3),
            Section::make('Secondary button')->schema([
                TextInput::make('secondary_label')->maxLength(60),
                Select::make('secondary_action')->options(CtaAction::class)->native(false)->live(),
                TextInput::make('secondary_value')->label('Link')->maxLength(500)->visible(fn (Get $get): bool => $needsValue($get, 'secondary_action'))
                    ->rules(['nullable', 'regex:#^(/[^\s]*|https?://[^\s]+|[a-z0-9\.\-]+)$#']),
            ])->columns(3),
            Section::make('WhatsApp')->schema([
                Textarea::make('whatsapp_message')->rows(2)->maxLength(300)->helperText('Use {entity} for the current service or product name. The number comes from Global Settings.')->columnSpanFull(),
            ])->visible($usesWhatsapp),
        ]);
    }

    protected static function value(mixed $state): ?string
    {
        return $state instanceof CtaAction ? $state->value : $state;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->searchable()->sortable()->description(fn (Cta $record): string => $record->key),
                TextColumn::make('primary_label')->label('Button'),
                TextColumn::make('primary_action')->badge()->color('gray'),
                TextColumn::make('variant')->badge()->color('gray'),
                TextColumn::make('click_count')->label('Clicks')->sortable(),
                IconColumn::make('is_active')->label('Active')->boolean(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageCtas::route('/')];
    }
}
