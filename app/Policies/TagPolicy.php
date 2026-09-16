<?php

namespace App\Policies;

class TagPolicy extends PermissionPolicy
{
    protected string $subject = 'tags';
}
