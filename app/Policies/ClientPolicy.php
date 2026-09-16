<?php

namespace App\Policies;

class ClientPolicy extends PermissionPolicy
{
    protected string $subject = 'clients';
}
