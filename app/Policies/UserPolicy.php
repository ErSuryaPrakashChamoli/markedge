<?php

namespace App\Policies;

class UserPolicy extends PermissionPolicy
{
    protected string $subject = 'users';
}
