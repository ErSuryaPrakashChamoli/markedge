<?php

namespace App\Policies;

class CtaClickPolicy extends PermissionPolicy
{
    protected string $subject = 'ctas';
}
