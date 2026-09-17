<?php

namespace App\Filament\Resources\AutomationRules;

use App\Automation\RuleVocabulary;
use App\Enums\AutomationTrigger;
use App\Enums\FollowUpType;
use App\Enums\LeadPriority;
use App\Enums\LeadStatus;
use App\Filament\Resources\AutomationRules\Pages\CreateAutomationRule;
use App\Filament\Resources\AutomationRules\Pages\EditAutomationRule;
use App\Filament\Resources\AutomationRules\Pages\ListAutomationRules;
use App\Models\AutomationRule;
use App\Models\User;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Configurable rules: trigger → conditions → actions, limited to the RuleVocabulary.
 */
class AutomationRuleResource extends Resource
{
    protected static ?string $model = AutomationRule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBolt;

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Automation rules';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Rule')->schema([
                TextInput::make('name')->required()->maxLength(120),
                Select::make('trigger')->options(AutomationTrigger::class)->required()->native(false),
                Toggle::make('is_active')->label('Active')->inline(false),
                TextInput::make('sort_order')->numeric()->default(0),
            ])->columns(2),
            Section::make('Conditions')->description('All conditions must match. Leave empty to act on every occurrence.')->schema([
                Repeater::make('conditions')->defaultItems(0)->schema([
                    Select::make('field')->options(array_combine(array_keys(RuleVocabulary::FIELDS), array_keys(RuleVocabulary::FIELDS)))->required()->native(false),
                    Select::make('operator')->options(array_combine(RuleVocabulary::OPERATORS, RuleVocabulary::OPERATORS))->required()->native(false),
                    TextInput::make('value')->maxLength(200)->helperText('For in / not_in separate values with commas.'),
                ])->columns(3)->columnSpanFull(),
            ]),
            Section::make('Actions')->description('Run in order through the same workflow the team uses.')->schema([
                Repeater::make('actions')->minItems(1)->schema([
                    Select::make('type')->options(array_combine(array_keys(RuleVocabulary::ACTIONS), array_map(fn (string $a) => ucfirst(str_replace('_', ' ', $a)), array_keys(RuleVocabulary::ACTIONS))))->required()->native(false)->live(),
                    Select::make('user_id')->label('Owner')->options(fn () => User::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'))->native(false)->visible(fn ($get) => in_array($get('type'), ['assign_owner', 'schedule_follow_up'], true)),
                    Select::make('priority')->options(LeadPriority::class)->native(false)->visible(fn ($get) => $get('type') === 'set_priority'),
                    TextInput::make('team')->maxLength(80)->visible(fn ($get) => $get('type') === 'set_team'),
                    Select::make('status')->label('Stage')->options(LeadStatus::class)->native(false)->visible(fn ($get) => $get('type') === 'move_stage'),
                    TextInput::make('reason')->maxLength(80)->visible(fn ($get) => $get('type') === 'move_stage'),
                    TextInput::make('hours')->numeric()->minValue(1)->visible(fn ($get) => $get('type') === 'schedule_follow_up'),
                    Select::make('type_of_follow_up')->label('Follow-up type')->options(FollowUpType::class)->native(false)->visible(fn ($get) => $get('type') === 'schedule_follow_up'),
                    Select::make('channel')->options(['database' => 'Admin notification', 'mail' => 'E-mail', 'webhook' => 'Webhook'])->native(false)->visible(fn ($get) => $get('type') === 'notify'),
                    TextInput::make('recipients')->helperText('User ids for admin notifications, e-mail addresses for mail, comma separated. Empty: the lead owner.')->maxLength(500)->visible(fn ($get) => $get('type') === 'notify'),
                    TextInput::make('subject')->maxLength(200)->visible(fn ($get) => $get('type') === 'notify'),
                    Textarea::make('body')->rows(2)->maxLength(2000)->helperText('Placeholders: {id} {name} {company} {status} {priority} {source}')->visible(fn ($get) => in_array($get('type'), ['notify', 'add_note'], true)),
                    Textarea::make('note')->rows(2)->maxLength(500)->visible(fn ($get) => $get('type') === 'schedule_follow_up'),
                ])->columns(2)->columnSpanFull()->itemLabel(fn (array $state): ?string => $state['type'] ?? null),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('trigger')->badge(),
                IconColumn::make('is_active')->label('Active')->boolean(),
                TextColumn::make('run_count')->label('Runs'),
                TextColumn::make('last_run_at')->dateTime('d M Y H:i')->placeholder('never'),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    /**
     * Normalises repeater data to the engine's shape and validates it against the vocabulary.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function normalise(array $data): array
    {
        $conditions = [];

        foreach ($data['conditions'] ?? [] as $condition) {
            $value = $condition['value'] ?? null;

            if (in_array($condition['operator'] ?? '', ['in', 'not_in'], true) && is_string($value)) {
                $value = array_values(array_filter(array_map('trim', explode(',', $value)), fn ($v) => $v !== ''));
            }

            $conditions[] = ['field' => $condition['field'] ?? null, 'operator' => $condition['operator'] ?? null, 'value' => $value];
        }

        $data['conditions'] = RuleVocabulary::validateConditions($conditions);
        $data['actions'] = RuleVocabulary::validateActions(array_map(fn ($a) => array_filter((array) $a, fn ($v) => $v !== null && $v !== ''), $data['actions'] ?? []));

        return $data;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAutomationRules::route('/'),
            'create' => CreateAutomationRule::route('/create'),
            'edit' => EditAutomationRule::route('/{record}/edit'),
        ];
    }
}
