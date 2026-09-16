<?php

namespace App\Editorial;

use App\Events\Content\ContentApproved;
use App\Events\Content\ContentAssigned;
use App\Events\Content\ContentChangesRequested;
use App\Events\Content\ContentExpiringSoon;
use App\Events\Content\ContentPublished;
use App\Events\Content\ContentScheduled;
use App\Events\Content\ContentSubmittedForReview;
use App\Events\Content\EditorialEvent;
use App\Filament\Pages\EditorialDesk;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Who hears about which editorial event. Only meaningful workflow moments notify; field edits
 * never do. The actor is never notified about their own action.
 */
class EditorialNotifier
{
    public function notify(EditorialEvent $event): void
    {
        $record = WorkflowModels::find($event->type(), $event->id());

        if ($record === null) {
            return;
        }

        $recipients = $this->recipientsFor($event, $record)
            ->reject(fn (User $user): bool => $user->id === $event->actorId() || ! $user->is_active)
            ->unique('id');

        if ($recipients->isEmpty()) {
            return;
        }

        $title = WorkflowModels::titleOf($record);
        $type = WorkflowModels::label($event->type());
        $reason = $event->context()['reason'] ?? null;

        $notification = Notification::make()
            ->title(match (true) {
                $event instanceof ContentSubmittedForReview => "{$type} awaiting your review: {$title}",
                $event instanceof ContentChangesRequested => "Changes requested on {$title}",
                $event instanceof ContentApproved => "{$title} was approved",
                $event instanceof ContentScheduled => "{$title} is scheduled",
                $event instanceof ContentPublished => "{$title} is live",
                $event instanceof ContentAssigned => "You were assigned to {$title}",
                $event instanceof ContentExpiringSoon => "{$title} unpublishes soon",
                default => ucfirst($event->label()).": {$title}",
            })
            ->body(is_string($reason) ? mb_strimwidth($reason, 0, 300, '…') : null)
            ->icon('heroicon-o-pencil-square')
            ->actions([Action::make('open')->label('Open editorial desk')->url(EditorialDesk::getUrl(['type' => $event->type(), 'record' => $event->id()]))]);

        $notification->sendToDatabase($recipients->all());
    }

    /**
     * @return Collection<int, User>
     */
    protected function recipientsFor(EditorialEvent $event, Model $record): Collection
    {
        $ids = match (true) {
            $event instanceof ContentSubmittedForReview => [$record->reviewer_id],
            $event instanceof ContentChangesRequested => [$record->owner_id, $record->created_by],
            $event instanceof ContentApproved => [$record->owner_id, $record->created_by],
            $event instanceof ContentScheduled, $event instanceof ContentPublished => [$record->owner_id, $record->created_by, $record->reviewer_id],
            $event instanceof ContentAssigned => array_values(array_filter($event->context())),
            $event instanceof ContentExpiringSoon => [$record->owner_id, $record->reviewer_id, $record->created_by],
            default => [],
        };

        $ids = array_values(array_unique(array_filter(array_map('intval', array_filter($ids)))));

        return $ids === [] ? collect() : User::query()->whereKey($ids)->get();
    }
}
