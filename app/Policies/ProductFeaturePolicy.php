<?php

namespace App\Policies;

class ProductFeaturePolicy extends PermissionPolicy
{
    protected string $subject = 'products';
}
