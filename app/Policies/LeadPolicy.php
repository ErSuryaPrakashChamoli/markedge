<?php

namespace App\Policies;

class LeadPolicy extends PermissionPolicy
{
    protected string $subject = 'leads';
}
