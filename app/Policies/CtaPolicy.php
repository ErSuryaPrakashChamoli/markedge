<?php

namespace App\Policies;

class CtaPolicy extends PermissionPolicy
{
    protected string $subject = 'ctas';
}
