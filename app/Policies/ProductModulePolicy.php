<?php

namespace App\Policies;

class ProductModulePolicy extends PermissionPolicy
{
    protected string $subject = 'products';
}
