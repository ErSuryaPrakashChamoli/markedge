<?php

namespace App\Events\Content;

class ContentPublished extends ContentWorkflowEvent
{
    public function label(): string
    {
        return 'published';
    }
}
