<?php

namespace App\Policies;

class ProductDocumentPolicy extends PermissionPolicy
{
    protected string $subject = 'products';
}
