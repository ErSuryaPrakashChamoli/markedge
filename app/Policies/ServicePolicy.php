<?php

namespace App\Policies;

class ServicePolicy extends PermissionPolicy
{
    protected string $subject = 'services';
}
