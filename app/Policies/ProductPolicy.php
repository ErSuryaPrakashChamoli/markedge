<?php

namespace App\Policies;

class ProductPolicy extends PermissionPolicy
{
    protected string $subject = 'products';
}
