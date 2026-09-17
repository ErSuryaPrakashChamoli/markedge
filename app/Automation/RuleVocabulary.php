<?php

namespace App\Automation;

use App\Enums\LeadPriority;
use App\Enums\LeadStatus;
use BackedEnum;
use Illuminate\Validation\ValidationException;

/**
 * The closed vocabulary rules may use. Anything outside it is rejected at save time so the engine
 * never evaluates arbitrary fields or performs unlisted actions.
 */
class RuleVocabulary
{
    /** @var array<string, string> field => type */
    public const array FIELDS = [
        'status' => 'enum', 'priority' => 'enum', 'team' => 'string', 'last_source' => 'string', 'first_source' => 'string',
        'last_medium' => 'string', 'campaign_id' => 'int', 'form_id' => 'int', 'product_id' => 'int', 'service_id' => 'int',
        'industry_id' => 'int', 'assigned_to' => 'int', 'country' => 'string', 'hours_in_stage' => 'number', 'hours_since_created' => 'number',
        'hours_awaiting_contact' => 'number', 'follow_up_overdue_hours' => 'number', 'is_duplicate' => 'bool',
    ];

    /** @var array<int, string> */
    public const array OPERATORS = ['eq', 'neq', 'in', 'not_in', 'gt', 'gte', 'lt', 'lte', 'empty', 'not_empty'];

    /** @var array<string, array<int, string>> action => required parameters */
    public const array ACTIONS = [
        'assign_owner' => ['user_id'],
        'set_priority' => ['priority'],
        'set_team' => ['team'],
        'move_stage' => ['status'],
        'add_note' => ['body'],
        'schedule_follow_up' => ['hours'],
        'notify' => ['channel', 'subject'],
    ];

    /**
     * @param  array<int, mixed>|null  $conditions
     * @return array<int, array{field: string, operator: string, value: mixed}>
     */
    public static function validateConditions(?array $conditions): array
    {
        $clean = [];

        foreach ($conditions ?? [] as $index => $condition) {
            if (! is_array($condition) || ! isset(self::FIELDS[$condition['field'] ?? '']) || ! in_array($condition['operator'] ?? '', self::OPERATORS, true)) {
                throw ValidationException::withMessages(["conditions.{$index}" => 'Unknown field or operator.']);
            }

            $clean[] = ['field' => $condition['field'], 'operator' => $condition['operator'], 'value' => $condition['value'] ?? null];
        }

        return $clean;
    }

    /**
     * @param  array<int, mixed>|null  $actions
     * @return array<int, array<string, mixed>>
     */
    public static function validateActions(?array $actions): array
    {
        $clean = [];

        foreach ($actions ?? [] as $index => $action) {
            $action = is_array($action) ? array_map(fn ($value) => $value instanceof BackedEnum ? $value->value : $value, $action) : $action;
            $type = is_array($action) ? ($action['type'] ?? null) : null;

            if (! is_string($type) || ! isset(self::ACTIONS[$type])) {
                throw ValidationException::withMessages(["actions.{$index}" => 'Unknown action type.']);
            }

            foreach (self::ACTIONS[$type] as $parameter) {
                if (blank($action[$parameter] ?? null)) {
                    throw ValidationException::withMessages(["actions.{$index}" => "Action {$type} needs {$parameter}."]);
                }
            }

            if ($type === 'set_priority' && LeadPriority::tryFrom((string) $action['priority']) === null) {
                throw ValidationException::withMessages(["actions.{$index}" => 'Unknown priority.']);
            }

            if ($type === 'move_stage' && LeadStatus::tryFrom((string) $action['status']) === null) {
                throw ValidationException::withMessages(["actions.{$index}" => 'Unknown stage.']);
            }

            $clean[] = array_intersect_key($action, array_flip(['type', ...self::ACTIONS[$type], 'type_of_follow_up', 'note', 'recipients', 'body', 'user_id']));
        }

        if ($clean === []) {
            throw ValidationException::withMessages(['actions' => 'A rule needs at least one action.']);
        }

        return $clean;
    }
}
