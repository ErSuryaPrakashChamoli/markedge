<?php

namespace App\Policies;

class AutomationRulePolicy extends PermissionPolicy
{
    protected string $subject = 'automation';
}
