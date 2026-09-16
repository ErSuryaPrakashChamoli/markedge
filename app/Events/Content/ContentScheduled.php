<?php

namespace App\Events\Content;

class ContentScheduled extends ContentWorkflowEvent
{
    public function label(): string
    {
        return 'scheduled';
    }
}
