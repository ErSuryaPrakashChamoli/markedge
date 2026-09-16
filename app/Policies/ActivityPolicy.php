<?php

namespace App\Policies;

class ActivityPolicy extends PermissionPolicy
{
    protected string $subject = 'activity';
}
