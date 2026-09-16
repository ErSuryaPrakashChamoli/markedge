<?php

namespace App\Policies;

class CampaignPolicy extends PermissionPolicy
{
    protected string $subject = 'campaigns';
}
