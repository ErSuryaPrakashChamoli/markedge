<?php

namespace App\Events\Content;

class ContentSubmittedForReview extends ContentWorkflowEvent
{
    public function label(): string
    {
        return 'submitted for review';
    }
}
