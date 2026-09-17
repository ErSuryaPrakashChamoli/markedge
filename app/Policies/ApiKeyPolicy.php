<?php

namespace App\Policies;

class ApiKeyPolicy extends PermissionPolicy
{
    protected string $subject = 'api_keys';
}
