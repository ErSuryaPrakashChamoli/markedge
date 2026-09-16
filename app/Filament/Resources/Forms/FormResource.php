<?php

namespace App\Filament\Resources\Forms;

use App\Enums\FormSuccessMode;
use App\Enums\FormType;
use App\Filament\Resources\Forms\Pages\CreateForm;
use App\Filament\Resources\Forms\Pages\EditForm;
use App\Filament\Resources\Forms\Pages\ListForms;
use App\Filament\Resources\Forms\RelationManagers\FieldsRelationManager;
use App\Models\Form;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

/**
 * Hybrid form definitions: core lead fields are toggled here, extra fields live in the relation manager.
 */
class FormResource extends Resource
{
    protected static ?string $model = Form::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxArrowDown;

    protected static string|UnitEnum|null $navigationGroup = 'Marketing';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Form')->tabs([
                Tab::make('Setup')->icon(Heroicon::OutlinedInboxArrowDown)->schema([
                    Section::make()->schema([
                        TextInput::make('name')->required()->maxLength(120),
                        TextInput::make('key')->required()->maxLength(60)->regex('/^[a-z0-9-]+$/')->unique(ignoreRecord: true)->helperText('Identifier used by blocks and pages.'),
                        Select::make('type')->options(FormType::class)->required()->native(false)->helperText('Used for reporting; it does not restrict fields.'),
                        Toggle::make('is_active')->label('Active')->default(true)->inline(false),
                        TextInput::make('heading')->maxLength(160),
                        TextInput::make('submit_label')->default('Submit')->required()->maxLength(40),
                        Textarea::make('intro')->rows(2)->maxLength(400)->columnSpanFull(),
                    ])->columns(2),
                    Section::make('Protection')->schema([
                        Toggle::make('honeypot_enabled')->label('Honeypot spam trap')->default(true)->inline(false),
                        Toggle::make('requires_consent')->label('Require privacy consent checkbox')->live()->inline(false),
                        Textarea::make('consent_text')->label('Consent statement')->rows(2)->maxLength(500)->placeholder(Form::DEFAULT_CONSENT_TEXT)->helperText('Shown next to the checkbox and stored with every lead that accepts it.')->visible(fn (Get $get): bool => (bool) $get('requires_consent')),
                    ])->columns(2),
                ]),
                Tab::make('Core fields')->icon(Heroicon::OutlinedListBullet)->schema(static::coreFieldsSchema()),
                Tab::make('After submission')->icon(Heroicon::OutlinedPaperAirplane)->schema([
                    Section::make('Success')->schema([
                        Select::make('success_mode')->options(FormSuccessMode::class)->default(FormSuccessMode::Message)->required()->native(false)->live(),
                        Select::make('success_page_id')->label('Redirect to page')->relationship('successPage', 'title')->searchable()->preload()->native(false)
                            ->visible(fn (Get $get): bool => in_array($get('success_mode'), [FormSuccessMode::Redirect, FormSuccessMode::Redirect->value], true)),
                        Textarea::make('success_message')->rows(2)->maxLength(400)->columnSpanFull(),
                    ])->columns(2),
                    Section::make('Notifications')->schema([
                        TagsInput::make('notify_emails')->label('Notify these addresses')->placeholder('Add an email and press enter')->nestedRecursiveRules(['email'])->helperText('Falls back to the global lead notification list when empty.')->columnSpanFull(),
                        Toggle::make('auto_reply_enabled')->label('Send an auto-reply to the enquirer')->live()->inline(false),
                        TextInput::make('auto_reply_subject')->maxLength(160)->visible(fn (Get $get): bool => (bool) $get('auto_reply_enabled')),
                        Textarea::make('auto_reply_body')->rows(4)->visible(fn (Get $get): bool => (bool) $get('auto_reply_enabled'))->columnSpanFull(),
                    ])->columns(2),
                ]),
            ])->columnSpanFull()->persistTabInQueryString(),
        ]);
    }

    /**
     * One fieldset per core lead column (architecture §19.2).
     *
     * @return array<int, Fieldset>
     */
    protected static function coreFieldsSchema(): array
    {
        return array_map(fn (string $field, int $index): Fieldset => Fieldset::make(ucfirst($field))->schema([
            Toggle::make("core_fields.{$field}.enabled")->label('Show')->default(in_array($field, ['name', 'email'], true))->inline(false),
            Toggle::make("core_fields.{$field}.required")->label('Required')->default(in_array($field, ['name', 'email'], true))->inline(false),
            TextInput::make("core_fields.{$field}.label")->label('Label')->placeholder(ucfirst($field))->maxLength(60),
            TextInput::make("core_fields.{$field}.placeholder")->label('Placeholder')->maxLength(80),
            TextInput::make("core_fields.{$field}.sort_order")->label('Order')->numeric()->default($index),
        ])->columns(5), Form::CORE_FIELDS, array_keys(Form::CORE_FIELDS));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->searchable()->sortable()->description(fn (Form $record): string => $record->key),
                TextColumn::make('type')->badge()->color('gray'),
                TextColumn::make('fields_count')->counts('fields')->label('Extra fields'),
                TextColumn::make('leads_count')->counts('leads')->label('Leads'),
                IconColumn::make('is_active')->label('Active')->boolean(),
            ])
            ->filters([SelectFilter::make('type')->options(FormType::class), TrashedFilter::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make(), RestoreBulkAction::make()])]);
    }

    public static function getRelations(): array
    {
        return [FieldsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListForms::route('/'),
            'create' => CreateForm::route('/create'),
            'edit' => EditForm::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
