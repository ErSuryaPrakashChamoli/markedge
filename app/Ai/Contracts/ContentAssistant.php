<?php

namespace App\Ai\Contracts;

use App\Ai\AiResponse;

/**
 * Editorial helper: outlines, rewrites and meta suggestions. Output is always a suggestion an
 * editor reviews; nothing is published by an implementation.
 */
interface ContentAssistant
{
    /**
     * @param  array<string, mixed>  $context  entity type, title, existing text, target length
     */
    public function suggest(string $task, array $context = []): AiResponse;
}
