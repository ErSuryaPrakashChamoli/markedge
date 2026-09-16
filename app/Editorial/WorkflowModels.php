<?php

namespace App\Editorial;

use App\Models\Concerns\HasEditorialWorkflow;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * The content types that take part in the editorial workflow, resolved from the morph map.
 */
class WorkflowModels
{
    /**
     * @return array<string, class-string<Model>> alias => class
     */
    public static function all(): array
    {
        return array_filter(
            Relation::morphMap(),
            fn (string $class): bool => in_array(HasEditorialWorkflow::class, class_uses_recursive($class), true),
        );
    }

    /**
     * @return class-string<Model>|null
     */
    public static function classFor(string $alias): ?string
    {
        return self::all()[$alias] ?? null;
    }

    public static function find(string $alias, int $id): ?Model
    {
        $class = self::classFor($alias);

        return $class ? $class::query()->withTrashed()->find($id) : null;
    }

    public static function label(string $alias): string
    {
        return ucfirst(str_replace('_', ' ', $alias));
    }

    public static function titleOf(Model $record): string
    {
        return (string) ($record->title ?? $record->name ?? $record->getKey());
    }
}
