<?php

namespace App\Policies;

class RolePolicy extends PermissionPolicy
{
    protected string $subject = 'roles';
}
