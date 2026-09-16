<?php

namespace App\Policies;

class SocialLinkPolicy extends PermissionPolicy
{
    protected string $subject = 'settings';
}
