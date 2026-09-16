<?php

namespace App\Policies;

class FormPolicy extends PermissionPolicy
{
    protected string $subject = 'forms';
}
