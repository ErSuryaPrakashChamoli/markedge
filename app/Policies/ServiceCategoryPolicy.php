<?php

namespace App\Policies;

class ServiceCategoryPolicy extends PermissionPolicy
{
    protected string $subject = 'service_categories';
}
