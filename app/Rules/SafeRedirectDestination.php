<?php

namespace App\Rules;

use App\Models\Redirect;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Internal paths always; external URLs only to hosts on the configured allow-list.
 */
class SafeRedirectDestination implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! Redirect::isSafeDestination($value)) {
            $fail('The destination must be a site path such as /new-page, or an https URL on an approved host.');
        }
    }
}
