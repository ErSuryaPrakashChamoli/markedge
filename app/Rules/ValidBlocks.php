<?php

namespace App\Rules;

use App\Cms\Blocks\BlockRegistry;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Rejects block trees with unknown types, disallowed hosts or invalid data.
 */
class ValidBlocks implements ValidationRule
{
    public function __construct(private readonly string $host) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null) {
            return;
        }

        if (! is_array($value)) {
            $fail('The blocks must be a list.');

            return;
        }

        $errors = app(BlockRegistry::class)->validate($value, $this->host);

        foreach ($errors->all() as $message) {
            $fail($message);
        }
    }
}
