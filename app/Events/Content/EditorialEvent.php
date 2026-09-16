<?php

namespace App\Events\Content;

/**
 * Marker for editorial workflow events so one listener can subscribe to all of them.
 */
interface EditorialEvent
{
    public function type(): string;

    public function id(): int;

    public function actorId(): ?int;

    /**
     * @return array<string, mixed>
     */
    public function context(): array;
}
