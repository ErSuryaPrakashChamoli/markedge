<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NotReservedSlug implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value) && in_array($value, config('markedge.reserved_slugs', []), true)) {
            $fail("\"{$value}\" is reserved for a built-in section of the website.");
        }
    }
}
