<?php

namespace App\Events\Content;

class ContentChangesRequested extends ContentWorkflowEvent
{
    public function label(): string
    {
        return 'changes requested';
    }
}
