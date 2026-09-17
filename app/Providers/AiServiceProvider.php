<?php

namespace App\Providers;

use App\Ai\Contracts\ContentAssistant;
use App\Ai\Contracts\KnowledgeAssistant;
use App\Ai\Contracts\LeadSummarizer;
use App\Ai\Contracts\SalesAssistant;
use App\Ai\UnavailableAssistant;
use Illuminate\Support\ServiceProvider;

/**
 * AI-readiness bindings. Phase 16 ships the interfaces and the honest "unavailable" implementation
 * only; no model, key or provider SDK is part of the codebase.
 */
class AiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        foreach ([LeadSummarizer::class, ContentAssistant::class, SalesAssistant::class, KnowledgeAssistant::class] as $contract) {
            $this->app->bind($contract, UnavailableAssistant::class);
        }
    }

    public static function isConfigured(): bool
    {
        return false;
    }

    public static function status(): string
    {
        return config('markedge.ai.provider') ? 'Provider named in config but no implementation is installed: NOT CONFIGURED' : 'NOT CONFIGURED (no AI provider)';
    }
}
