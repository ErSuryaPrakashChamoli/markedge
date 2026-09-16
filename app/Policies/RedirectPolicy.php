<?php

namespace App\Policies;

class RedirectPolicy extends PermissionPolicy
{
    protected string $subject = 'redirects';
}
