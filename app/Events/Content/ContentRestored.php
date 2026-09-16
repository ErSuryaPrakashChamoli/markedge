<?php

namespace App\Events\Content;

class ContentRestored extends ContentWorkflowEvent
{
    public function label(): string
    {
        return 'restored';
    }
}
