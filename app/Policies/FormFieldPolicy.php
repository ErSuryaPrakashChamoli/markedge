<?php

namespace App\Policies;

class FormFieldPolicy extends PermissionPolicy
{
    protected string $subject = 'forms';
}
