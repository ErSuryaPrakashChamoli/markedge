<?php

namespace App\Policies;

class LandingPagePolicy extends PermissionPolicy
{
    protected string $subject = 'landing_pages';
}
