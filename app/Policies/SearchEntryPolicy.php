<?php

namespace App\Policies;

class SearchEntryPolicy extends PermissionPolicy
{
    protected string $subject = 'seo';
}
