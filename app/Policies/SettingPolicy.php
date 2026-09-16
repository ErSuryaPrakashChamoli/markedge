<?php

namespace App\Policies;

class SettingPolicy extends PermissionPolicy
{
    protected string $subject = 'settings';
}
