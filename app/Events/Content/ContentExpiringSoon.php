<?php

namespace App\Events\Content;

class ContentExpiringSoon extends ContentWorkflowEvent
{
    public function label(): string
    {
        return 'expiring soon';
    }
}
