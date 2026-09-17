<?php

namespace App\Automation;

use App\Models\Lead;
use BackedEnum;

/**
 * Evaluates rule conditions against a lead. All conditions must match (AND). Values are compared
 * as scalars; enums by their stored value.
 */
class ConditionMatcher
{
    /**
     * @param  array<int, array{field: string, operator: string, value: mixed}>  $conditions
     * @param  array<string, mixed>  $context  event-specific values (e.g. follow_up_overdue_hours)
     */
    public function matches(Lead $lead, array $conditions, array $context = []): bool
    {
        foreach ($conditions as $condition) {
            if (! $this->matchesOne($this->valueOf($lead, $condition['field'], $context), $condition['operator'], $condition['value'] ?? null)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    protected function valueOf(Lead $lead, string $field, array $context): mixed
    {
        $value = match ($field) {
            'hours_in_stage' => ($lead->stage_entered_at ?? $lead->created_at)?->diffInMinutes(now()) / 60,
            'hours_since_created' => $lead->created_at?->diffInMinutes(now()) / 60,
            'hours_awaiting_contact' => $lead->hoursAwaitingContact(),
            'is_duplicate' => $lead->duplicate_of_lead_id !== null,
            'follow_up_overdue_hours' => $context['follow_up_overdue_hours'] ?? ($lead->next_follow_up_at?->isPast() ? $lead->next_follow_up_at->diffInMinutes(now()) / 60 : null),
            default => $context[$field] ?? $lead->{$field},
        };

        return $value instanceof BackedEnum ? $value->value : $value;
    }

    protected function matchesOne(mixed $actual, string $operator, mixed $expected): bool
    {
        $expectedList = is_array($expected) ? $expected : (is_string($expected) ? array_map('trim', explode(',', $expected)) : []);
        $list = array_map(fn ($v) => is_string($v) ? mb_strtolower($v) : $v, $expectedList);
        $normalised = is_string($actual) ? mb_strtolower($actual) : $actual;
        $expectedNormalised = is_string($expected) ? mb_strtolower($expected) : $expected;

        return match ($operator) {
            'eq' => $normalised == $expectedNormalised,
            'neq' => $normalised != $expectedNormalised,
            'in' => in_array($normalised, $list, false),
            'not_in' => ! in_array($normalised, $list, false),
            'gt' => is_numeric($actual) && is_numeric($expected) && $actual > $expected,
            'gte' => is_numeric($actual) && is_numeric($expected) && $actual >= $expected,
            'lt' => is_numeric($actual) && is_numeric($expected) && $actual < $expected,
            'lte' => is_numeric($actual) && is_numeric($expected) && $actual <= $expected,
            'empty' => blank($actual),
            'not_empty' => filled($actual),
            default => false,
        };
    }
}
