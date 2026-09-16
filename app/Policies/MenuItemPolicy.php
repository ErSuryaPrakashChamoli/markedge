<?php

namespace App\Policies;

class MenuItemPolicy extends PermissionPolicy
{
    protected string $subject = 'menus';
}
