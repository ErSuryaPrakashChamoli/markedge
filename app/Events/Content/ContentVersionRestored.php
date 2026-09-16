<?php

namespace App\Events\Content;

class ContentVersionRestored extends ContentWorkflowEvent
{
    public function label(): string
    {
        return 'version restored';
    }
}
