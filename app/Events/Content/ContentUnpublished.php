<?php

namespace App\Events\Content;

class ContentUnpublished extends ContentWorkflowEvent
{
    public function label(): string
    {
        return 'unpublished';
    }
}
