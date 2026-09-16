<?php

namespace App\Events\Content;

class ContentApproved extends ContentWorkflowEvent
{
    public function label(): string
    {
        return 'approved';
    }
}
