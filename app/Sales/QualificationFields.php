<?php

namespace App\Sales;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;

/**
 * Qualification questions come from config/markedge.php (sales.qualification_fields) and are stored
 * as answers keyed by field on the lead. Unknown keys are dropped; every answer is bounded.
 */
class QualificationFields
{
    /**
     * @return array<int, array{key: string, label: string, type: string, options?: array<string, string>}>
     */
    public static function definitions(): array
    {
        $fields = config('markedge.sales.qualification_fields', []);

        return array_values(array_filter(is_array($fields) ? $fields : [], fn ($field): bool => is_array($field)
            && is_string($field['key'] ?? null) && preg_match('/^[a-z0-9_]{1,40}$/', $field['key']) === 1
            && is_string($field['label'] ?? null)
            && in_array($field['type'] ?? null, ['select', 'text', 'textarea', 'boolean', 'number'], true)));
    }

    /**
     * Form components bound to a state path such as "qualification".
     *
     * @return array<int, Component>
     */
    public static function components(string $statePath = 'qualification'): array
    {
        return array_map(function (array $field) use ($statePath): Component {
            $name = "{$statePath}.{$field['key']}";

            return match ($field['type']) {
                'select' => Select::make($name)->label($field['label'])->options($field['options'] ?? [])->native(false)->placeholder('Not captured'),
                'textarea' => Textarea::make($name)->label($field['label'])->rows(3)->maxLength(2000)->columnSpanFull(),
                'boolean' => Toggle::make($name)->label($field['label']),
                'number' => TextInput::make($name)->label($field['label'])->numeric()->minValue(0),
                default => TextInput::make($name)->label($field['label'])->maxLength(255),
            };
        }, self::definitions());
    }

    /**
     * Keep only configured keys, coerce by type and drop empty answers.
     *
     * @param  array<string, mixed>|null  $answers
     * @return array<string, mixed>|null
     */
    public static function sanitise(?array $answers): ?array
    {
        $clean = [];

        foreach (self::definitions() as $field) {
            $value = $answers[$field['key']] ?? null;

            if ($value === null || $value === '') {
                continue;
            }

            $clean[$field['key']] = match ($field['type']) {
                'select' => array_key_exists((string) $value, $field['options'] ?? []) ? (string) $value : null,
                'boolean' => (bool) $value,
                'number' => is_numeric($value) ? $value + 0 : null,
                'textarea' => mb_substr((string) $value, 0, 2000),
                default => mb_substr((string) $value, 0, 255),
            };

            if ($clean[$field['key']] === null) {
                unset($clean[$field['key']]);
            }
        }

        return $clean === [] ? null : $clean;
    }

    /**
     * Human-readable answers for display.
     *
     * @param  array<string, mixed>|null  $answers
     * @return array<string, string>
     */
    public static function display(?array $answers): array
    {
        $rows = [];

        foreach (self::definitions() as $field) {
            if (! array_key_exists($field['key'], $answers ?? [])) {
                continue;
            }

            $value = $answers[$field['key']];

            $rows[$field['label']] = match ($field['type']) {
                'select' => (string) (($field['options'] ?? [])[$value] ?? $value),
                'boolean' => $value ? 'Yes' : 'No',
                default => (string) $value,
            };
        }

        return $rows;
    }
}
