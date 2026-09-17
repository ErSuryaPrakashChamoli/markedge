<?php

namespace App\Ai\Contracts;

use App\Ai\AiResponse;

/**
 * Answers questions from published site and product content only.
 */
interface KnowledgeAssistant
{
    /**
     * @param  array<int, string>  $scopes  search types to draw from (see SearchTypes)
     */
    public function answer(string $question, array $scopes = []): AiResponse;
}
