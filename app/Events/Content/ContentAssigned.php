<?php

namespace App\Events\Content;

class ContentAssigned extends ContentWorkflowEvent
{
    public function label(): string
    {
        return 'assigned';
    }
}
