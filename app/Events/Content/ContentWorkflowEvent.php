<?php

namespace App\Events\Content;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Base for workflow events. Carries morph alias + id (never the model) so queued listeners
 * always load the current record.
 */
abstract class ContentWorkflowEvent implements EditorialEvent
{
    use Dispatchable;

    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(public string $type, public int $id, public ?int $actorId = null, public array $context = []) {}

    public static function for(Model $record, array $context = []): static
    {
        return new static($record->getMorphClass(), (int) $record->getKey(), auth()->id(), $context);
    }

    public function type(): string
    {
        return $this->type;
    }

    public function id(): int
    {
        return $this->id;
    }

    public function actorId(): ?int
    {
        return $this->actorId;
    }

    public function context(): array
    {
        return $this->context;
    }

    abstract public function label(): string;
}
