<?php

namespace App\Services\Cms;

use App\Cms\Blocks\BlockRegistry;
use App\Enums\EditorialCommentType;
use App\Enums\PublishStatus;
use App\Events\Content\ContentApproved;
use App\Events\Content\ContentArchived;
use App\Events\Content\ContentAssigned;
use App\Events\Content\ContentChangesRequested;
use App\Events\Content\ContentPublished;
use App\Events\Content\ContentRestored;
use App\Events\Content\ContentScheduled;
use App\Events\Content\ContentSubmittedForReview;
use App\Events\Content\ContentUnpublished;
use App\Models\Concerns\HasBlocks;
use App\Models\Concerns\HasEditorialWorkflow;
use App\Models\Concerns\Publishable;
use DateTimeInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\MessageBag;
use Illuminate\Validation\ValidationException;

/**
 * The single place status transitions happen (architecture §35, Phase 9 §4). Every transition
 * is checked against the allowed matrix, the caller's permission, the publish checklist, and is
 * written to the activity log under a row lock so concurrent actions cannot double-apply.
 *
 * Draft → Review (update) · Review → Draft (review) · Review/Draft/Scheduled → Published (publish)
 * Review/Draft → Scheduled (publish) · Published/Scheduled → Draft (publish) · any → Archived (publish)
 * Archived → Draft (publish)
 */
class Publisher
{
    /** @var array<string, array<int, string>> */
    protected const array TRANSITIONS = [
        'draft' => ['review', 'scheduled', 'published', 'archived'],
        'review' => ['draft', 'scheduled', 'published', 'archived'],
        'scheduled' => ['draft', 'published', 'archived'],
        'published' => ['draft', 'archived'],
        'archived' => ['draft'],
    ];

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

        if ($this->hasWorkflow($record) && $record->unpublish_at !== null && $record->unpublish_at->isPast()) {
            $errors->add('unpublish_at', 'The unpublish date is in the past. Clear it or move it into the future.');
        }

        return $errors;
    }

    public function submitForReview(Model $record): Model
    {
        $this->authorize($record, 'update');

        $record = $this->transition($record, PublishStatus::Review, 'submitted for review', $this->hasWorkflow($record) ? ['submitted_at' => now()] : []);
        event(ContentSubmittedForReview::for($record));

        return $record;
    }

    /**
     * A reviewer signs off; the record stays in review until someone with publish rights acts.
     */
    public function approve(Model $record): Model
    {
        $this->authorize($record, 'review');

        if ($record->status !== PublishStatus::Review) {
            throw ValidationException::withMessages(['status' => 'Only content in review can be approved.']);
        }

        $record->forceFill(['approved_at' => now(), 'approved_by' => auth()->id()])->saveQuietly();
        $this->log($record, 'approved', ['from' => 'review', 'to' => 'review', 'approved' => true]);
        event(ContentApproved::for($record));

        return $record;
    }

    /**
     * Returns content to Draft with a change request the author sees in the CMS.
     */
    public function requestChanges(Model $record, string $reason): Model
    {
        $this->authorize($record, 'review');

        $reason = trim($reason);

        if ($reason === '') {
            throw ValidationException::withMessages(['reason' => 'Please explain what needs to change.']);
        }

        $record = $this->transition($record, PublishStatus::Draft, 'changes requested', ['approved_at' => null, 'approved_by' => null], ['reason' => $reason]);

        if ($this->hasWorkflow($record)) {
            $record->editorialComments()->create(['user_id' => auth()->id(), 'type' => EditorialCommentType::ChangeRequest, 'body' => $reason]);
        }

        event(ContentChangesRequested::for($record, ['reason' => $reason]));

        return $record;
    }

    /**
     * @throws ValidationException
     */
    public function publish(Model $record): Model
    {
        $this->authorize($record, 'publish');
        $this->guard($record);

        $publishedAt = $record->published_at?->isPast() ? $record->published_at : now();
        $record = $this->transition($record, PublishStatus::Published, 'published', ['published_at' => $publishedAt]);
        event(ContentPublished::for($record));

        return $record;
    }

    /**
     * @throws ValidationException
     */
    public function schedule(Model $record, DateTimeInterface $publishAt, ?DateTimeInterface $unpublishAt = null): Model
    {
        $this->authorize($record, 'publish');

        if ($unpublishAt !== null && $unpublishAt <= $publishAt) {
            throw ValidationException::withMessages(['unpublish_at' => 'The unpublish date must be after the publish date.']);
        }

        if ($unpublishAt !== null && $this->hasWorkflow($record)) {
            $record->unpublish_at = $unpublishAt;
        }

        $this->guard($record);

        if ($publishAt <= now()) {
            $record = $this->transition($record, PublishStatus::Published, 'published', ['published_at' => now()] + ($unpublishAt && $this->hasWorkflow($record) ? ['unpublish_at' => $unpublishAt] : []));
            event(ContentPublished::for($record));

            return $record;
        }

        $record = $this->transition($record, PublishStatus::Scheduled, 'scheduled', ['published_at' => $publishAt] + ($unpublishAt && $this->hasWorkflow($record) ? ['unpublish_at' => $unpublishAt] : []));
        event(ContentScheduled::for($record, ['publish_at' => $publishAt->format(DATE_ATOM)]));

        return $record;
    }

    public function unpublish(Model $record, string $event = 'unpublished'): Model
    {
        $this->authorize($record, 'publish');

        $record = $this->transition($record, PublishStatus::Draft, $event, ['approved_at' => null, 'approved_by' => null]);
        event(ContentUnpublished::for($record));

        return $record;
    }

    public function archive(Model $record): Model
    {
        $this->authorize($record, 'publish');

        $record = $this->transition($record, PublishStatus::Archived, 'archived');
        event(ContentArchived::for($record));

        return $record;
    }

    /**
     * Archived → Draft (not to be confused with restoring a revision).
     */
    public function restore(Model $record): Model
    {
        $this->authorize($record, 'publish');

        $record = $this->transition($record, PublishStatus::Draft, 'restored');
        event(ContentRestored::for($record));

        return $record;
    }

    /**
     * Assignment changes nothing but ownership; URL, canonical, status and content stay as they are.
     */
    public function assign(Model $record, ?int $ownerId, ?int $reviewerId, bool $keepUnset = false): Model
    {
        $this->authorize($record, 'review');

        $changes = [];

        if (! $keepUnset || $ownerId !== null) {
            $changes['owner_id'] = $ownerId;
        }

        if (! $keepUnset || $reviewerId !== null) {
            $changes['reviewer_id'] = $reviewerId;
        }

        $before = ['owner_id' => $record->owner_id, 'reviewer_id' => $record->reviewer_id];
        $record->forceFill($changes)->saveQuietly();
        $this->log($record, 'assigned', ['before' => $before, 'after' => $changes]);
        event(ContentAssigned::for($record, $changes));

        return $record;
    }

    /**
     * Flips due scheduled records live. Returns the number published.
     */
    public function publishDue(): int
    {
        $count = 0;

        foreach ($this->publishableModels() as $class) {
            $class::query()->dueForPublishing()->with('seo')->chunkById(100, function ($records) use (&$count): void {
                foreach ($records as $record) {
                    try {
                        $this->guard($record);
                        $record = $this->transition($record, PublishStatus::Published, 'published (scheduled)');
                        event(ContentPublished::for($record));
                        $count++;
                    } catch (ValidationException $exception) {
                        $this->log($record, 'scheduled publish failed', ['errors' => $exception->errors()]);
                    }
                }
            });
        }

        return $count;
    }

    /**
     * Takes published records past their unpublish date offline (Draft, never Archived).
     */
    public function unpublishDue(): int
    {
        $count = 0;

        foreach ($this->publishableModels() as $class) {
            if (! in_array(HasEditorialWorkflow::class, class_uses_recursive($class), true)) {
                continue;
            }

            $class::query()->dueForUnpublishing()->chunkById(100, function ($records) use (&$count): void {
                foreach ($records as $record) {
                    $record = $this->transition($record, PublishStatus::Draft, 'unpublished (expired)', ['approved_at' => null, 'approved_by' => null]);
                    event(ContentUnpublished::for($record, ['expired' => true]));
                    $count++;
                }
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
     * @param  array<string, mixed>  $properties
     */
    protected function transition(Model $record, PublishStatus $status, string $event, array $extra = [], array $properties = []): Model
    {
        return DB::transaction(function () use ($record, $status, $event, $extra, $properties): Model {
            $locked = $record->newQueryWithoutScopes()->lockForUpdate()->find($record->getKey());

            if ($locked === null) {
                throw ValidationException::withMessages(['status' => 'The record no longer exists.']);
            }

            $from = $locked->status;

            if ($from === $status) {
                throw ValidationException::withMessages(['status' => 'The content is already '.strtolower($status->getLabel()).'.']);
            }

            if (! in_array($status->value, self::TRANSITIONS[$from->value] ?? [], true)) {
                throw ValidationException::withMessages(['status' => "Cannot move content from {$from->getLabel()} to {$status->getLabel()}."]);
            }

            $locked->forceFill(['status' => $status] + $this->filterColumns($locked, $extra))->save();

            $this->log($locked, $event, ['from' => $from->value, 'to' => $status->value] + $properties);

            $record->setRawAttributes($locked->getAttributes(), true);
            $record->syncOriginal();

            return $record;
        });
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    protected function filterColumns(Model $record, array $extra): array
    {
        if ($this->hasWorkflow($record)) {
            return $extra;
        }

        return array_diff_key($extra, array_flip(['submitted_at', 'approved_at', 'approved_by', 'unpublish_at']));
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    protected function log(Model $record, string $event, array $properties = []): void
    {
        activity('content')
            ->performedOn($record)
            ->causedBy(auth()->user())
            ->withProperties($properties)
            ->event($event)
            ->log(ucfirst($event));
    }

    /**
     * Only enforced for an authenticated actor; scheduled jobs run as the system.
     */
    protected function authorize(Model $record, string $ability): void
    {
        if (auth()->check() && ! Gate::allows($ability, $record)) {
            throw new AuthorizationException("You are not allowed to {$ability} this content.");
        }
    }

    protected function hasWorkflow(Model $record): bool
    {
        return in_array(HasEditorialWorkflow::class, class_uses_recursive($record), true);
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
