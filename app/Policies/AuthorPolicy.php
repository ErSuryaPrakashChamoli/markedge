<?php

namespace App\Policies;

class AuthorPolicy extends PermissionPolicy
{
    protected string $subject = 'authors';
}
