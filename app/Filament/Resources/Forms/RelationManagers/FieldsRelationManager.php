<?php

namespace App\Filament\Resources\Forms\RelationManagers;

use App\Enums\FormFieldType;
use App\Models\FormField;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Controlled extra-field editor. Validation is limited to an allow-list; no code is accepted.
 */
class FieldsRelationManager extends RelationManager
{
    protected static string $relationship = 'fields';

    protected static ?string $title = 'Extra fields';

    public function form(Schema $schema): Schema
    {
        $hasOptions = fn (Get $get): bool => in_array($get('type'), ['select', 'multiselect', 'radio', FormFieldType::Select, FormFieldType::Multiselect, FormFieldType::Radio], true);

        return $schema->components([
            Section::make()->schema([
                TextInput::make('label')->required()->maxLength(80)->live(onBlur: true)
                    ->afterStateUpdated(fn ($set, $get, ?string $state) => blank($get('key')) ? $set('key', str($state)->slug('_')->toString()) : null),
                TextInput::make('key')->required()->maxLength(40)->regex('/^[a-z][a-z0-9_]*$/')->helperText('Stored key, e.g. team_size.'),
                Select::make('type')->options(FormFieldType::class)->default(FormFieldType::Text)->required()->native(false)->live(),
                Select::make('width')->options(['full' => 'Full width', 'half' => 'Half width'])->default('full')->native(false),
                TextInput::make('placeholder')->maxLength(80),
                TextInput::make('help_text')->maxLength(160),
                Toggle::make('is_required')->label('Required')->inline(false),
                TextInput::make('sort_order')->numeric()->default(0),
            ])->columns(2),
            Section::make('Options')->schema([
                Select::make('options.source')->label('Option source')->options([
                    '' => 'Manual values', 'services' => 'Services', 'products' => 'Products', 'industries' => 'Industries', 'solutions' => 'Solutions',
                ])->native(false)->live(),
                TagsInput::make('options.values')->label('Values')->placeholder('Type a value and press enter')
                    ->visible(fn (Get $get): bool => blank($get('options.source'))),
            ])->columns(2)->visible($hasOptions),
            Section::make('Validation and mapping')->schema([
                TextInput::make('validation.min')->label('Minimum length or value')->numeric(),
                TextInput::make('validation.max')->label('Maximum length or value')->numeric(),
                Toggle::make('validation.url')->label('Must be a URL')->inline(false),
                Select::make('maps_to')->label('Store in lead column')->options(array_combine(FormField::MAPPABLE_COLUMNS, FormField::MAPPABLE_COLUMNS))->native(false)
                    ->helperText('Writes the value to a real lead column instead of the custom fields list.'),
            ])->columns(2)->collapsible(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('label')
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('label'),
                TextColumn::make('key')->color('gray'),
                TextColumn::make('type')->badge(),
                IconColumn::make('is_required')->label('Required')->boolean(),
                TextColumn::make('maps_to')->label('Maps to')->placeholder('—'),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
