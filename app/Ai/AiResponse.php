<?php

namespace App\Ai;

/**
 * Result envelope for every assistant interface. When no provider is configured the response is
 * "unavailable" with a reason; callers must render that state and never invent content.
 *
 * @param  array<string, mixed>  $data
 */
final readonly class AiResponse
{
    private function __construct(public bool $available, public ?string $text, public array $data, public ?string $reason, public ?string $provider) {}

    public static function unavailable(string $reason = 'NOT CONFIGURED: no AI provider is configured (markedge.ai.provider).'): self
    {
        return new self(false, null, [], $reason, null);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function of(string $provider, string $text, array $data = []): self
    {
        return new self(true, $text, $data, null, $provider);
    }
}
