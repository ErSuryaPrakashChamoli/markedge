<?php

namespace App\Policies;

class ArticleCategoryPolicy extends PermissionPolicy
{
    protected string $subject = 'article_categories';
}
