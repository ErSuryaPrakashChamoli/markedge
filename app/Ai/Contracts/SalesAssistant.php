<?php

namespace App\Ai\Contracts;

use App\Ai\AiResponse;
use App\Models\Lead;

/**
 * Next-step and reply drafting for a lead. Drafts are never sent automatically.
 */
interface SalesAssistant
{
    public function nextSteps(Lead $lead): AiResponse;

    public function draftReply(Lead $lead, string $intent): AiResponse;
}
