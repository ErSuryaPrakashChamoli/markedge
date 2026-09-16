<?php

namespace App\Events\Content;

class ContentArchived extends ContentWorkflowEvent
{
    public function label(): string
    {
        return 'archived';
    }
}
