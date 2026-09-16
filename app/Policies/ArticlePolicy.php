<?php

namespace App\Policies;

class ArticlePolicy extends PermissionPolicy
{
    protected string $subject = 'articles';
}
