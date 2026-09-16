<?php

namespace App\Services\Cms;

use App\Cms\Blocks\BlockRegistry;
use App\Enums\PublishStatus;
use App\Models\Concerns\HasBlocks;
use App\Models\Concerns\Publishable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\MessageBag;
use Illuminate\Validation\ValidationException;

/**
 * The single place status transitions happen (architecture §35). Every transition is
 * checked against the publish checklist and written to the activity log.
 */
class Publisher
{
    public function __construct(private readonly BlockRegistry $blocks) {}

    /**
     * Problems that must be fixed before the record can go live. Empty means publishable.
     */
    public function checklist(Model $record): MessageBag
    {
        $errors = new MessageBag;

        if (in_array(HasBlocks::class, class_uses_recursive($record), true)) {
            $errors->merge($this->blocks->validate($record->blocks, $record->getMorphClass()));
        }

        if (method_exists($record, 'publishChecklist')) {
            $errors->merge($record->publishChecklist());
        }

        return $errors;
    }

    public function submitForReview(Model $record): Model
    {
        return $this->transition($record, PublishStatus::Review, 'submitted for review');
    }

    /**
     * @throws ValidationException
     */
    public function publish(Model $record): Model
    {
        $this->guard($record);

        $publishedAt = $record->published_at?->isPast() ? $record->published_at : now();

        return $this->transition($record, PublishStatus::Published, 'published', ['published_at' => $publishedAt]);
    }

    /**
     * @throws ValidationException
     */
    public function schedule(Model $record, \DateTimeInterface $publishAt): Model
    {
        $this->guard($record);

        if ($publishAt <= now()) {
            return $this->transition($record, PublishStatus::Published, 'published', ['published_at' => now()]);
        }

        return $this->transition($record, PublishStatus::Scheduled, 'scheduled', ['published_at' => $publishAt]);
    }

    public function unpublish(Model $record): Model
    {
        return $this->transition($record, PublishStatus::Draft, 'unpublished');
    }

    public function archive(Model $record): Model
    {
        return $this->transition($record, PublishStatus::Archived, 'archived');
    }

    /**
     * Flips due scheduled records live. Returns the number published.
     */
    public function publishDue(): int
    {
        $count = 0;

        foreach ($this->publishableModels() as $class) {
            $class::query()->dueForPublishing()->each(function (Model $record) use (&$count): void {
                $this->transition($record, PublishStatus::Published, 'published (scheduled)');
                $count++;
            });
        }

        return $count;
    }

    /**
     * @return array<int, class-string<Model>>
     */
    public function publishableModels(): array
    {
        return array_values(array_filter(
            Relation::morphMap(),
            fn (string $class): bool => in_array(Publishable::class, class_uses_recursive($class), true),
        ));
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    protected function transition(Model $record, PublishStatus $status, string $event, array $extra = []): Model
    {
        $from = $record->status;

        $record->forceFill(['status' => $status] + $extra)->save();

        activity('content')
            ->performedOn($record)
            ->causedBy(auth()->user())
            ->withProperties(['from' => $from?->value, 'to' => $status->value])
            ->event($event)
            ->log(ucfirst($event));

        return $record;
    }

    /**
     * @throws ValidationException
     */
    protected function guard(Model $record): void
    {
        $errors = $this->checklist($record);

        if ($errors->isNotEmpty()) {
            throw ValidationException::withMessages($errors->toArray());
        }
    }
}
