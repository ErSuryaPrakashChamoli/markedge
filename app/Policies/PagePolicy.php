<?php

namespace App\Policies;

class PagePolicy extends PermissionPolicy
{
    protected string $subject = 'pages';
}
