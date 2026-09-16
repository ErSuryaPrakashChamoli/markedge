<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;

/**
 * Slug uniqueness across several tables that share a URL namespace (services and service categories).
 */
class UniqueSlugAcross implements ValidationRule
{
    /**
     * @param  array<int, class-string<Model>>  $models
     */
    public function __construct(private readonly array $models, private readonly ?Model $ignore = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        foreach ($this->models as $model) {
            $query = $model::query()->where('slug', $value);

            if (method_exists($model, 'withTrashed')) {
                $query->withTrashed();
            }

            if ($this->ignore instanceof $model) {
                $query->whereKeyNot($this->ignore->getKey());
            }

            if ($query->exists()) {
                $fail("The slug \"{$value}\" is already used under /services.");

                return;
            }
        }
    }
}
