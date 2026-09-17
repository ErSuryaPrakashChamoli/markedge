<?php

namespace App\Policies;

class ProductCapabilityPolicy extends PermissionPolicy
{
    protected string $subject = 'products';
}
