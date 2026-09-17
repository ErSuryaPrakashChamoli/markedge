<?php

namespace App\Ai;

use App\Ai\Contracts\ContentAssistant;
use App\Ai\Contracts\KnowledgeAssistant;
use App\Ai\Contracts\LeadSummarizer;
use App\Ai\Contracts\SalesAssistant;
use App\Models\Lead;

/**
 * The only implementation shipped in Phase 16. Every method reports NOT CONFIGURED so the UI can
 * show the state honestly. A real provider is bound in AiServiceProvider when one is configured.
 */
class UnavailableAssistant implements ContentAssistant, KnowledgeAssistant, LeadSummarizer, SalesAssistant
{
    public function summarize(Lead $lead): AiResponse
    {
        return AiResponse::unavailable();
    }

    public function suggest(string $task, array $context = []): AiResponse
    {
        return AiResponse::unavailable();
    }

    public function nextSteps(Lead $lead): AiResponse
    {
        return AiResponse::unavailable();
    }

    public function draftReply(Lead $lead, string $intent): AiResponse
    {
        return AiResponse::unavailable();
    }

    public function answer(string $question, array $scopes = []): AiResponse
    {
        return AiResponse::unavailable();
    }
}
