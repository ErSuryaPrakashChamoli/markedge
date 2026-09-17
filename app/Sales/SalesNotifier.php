<?php

namespace App\Sales;

use App\Enums\LeadStatus;
use App\Events\Sales\FollowUpDue;
use App\Events\Sales\LeadAssigned;
use App\Events\Sales\LeadStageChanged;
use App\Filament\Resources\Leads\LeadResource;
use App\Models\Lead;
use App\Models\LeadFollowUp;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

/**
 * Internal (database) notifications for the sales workflow. The actor never hears about their own
 * action, inactive users are skipped, and bodies carry no contact details.
 */
class SalesNotifier
{
    public function leadAssigned(LeadAssigned $event): void
    {
        $lead = Lead::query()->find($event->leadId);
        $owner = $event->ownerId ? User::query()->find($event->ownerId) : null;

        if ($lead === null || $owner === null || $owner->id === $event->actorId || ! $owner->is_active) {
            return;
        }

        $this->send($owner, "You were assigned enquiry #{$lead->id} ({$lead->name})", $lead, 'heroicon-o-user-plus');
    }

    public function stageChanged(LeadStageChanged $event): void
    {
        $lead = Lead::query()->with('assignee')->find($event->leadId);
        $owner = $lead?->assignee;

        // Assignment already notifies the owner; the automatic New → Assigned move stays quiet.
        if ($lead === null || $owner === null || $owner->id === $event->actorId || ! $owner->is_active || $event->to === LeadStatus::Assigned) {
            return;
        }

        $this->send($owner, "Enquiry #{$lead->id} moved to {$event->to->getLabel()}", $lead, 'heroicon-o-arrow-right-circle', $event->reason);
    }

    public function followUpDue(FollowUpDue $event): void
    {
        $followUp = LeadFollowUp::query()->with(['lead', 'owner'])->find($event->followUpId);
        $owner = $followUp?->owner;

        if ($followUp === null || $followUp->lead === null || $owner === null || ! $owner->is_active) {
            return;
        }

        $when = $followUp->due_at->isPast() ? 'was due '.$followUp->due_at->diffForHumans() : 'is due '.$followUp->due_at->diffForHumans();

        $this->send($owner, ucfirst($followUp->type->getLabel())." for enquiry #{$followUp->lead->id} {$when}", $followUp->lead, 'heroicon-o-bell-alert', $followUp->note);
    }

    protected function send(User $user, string $title, Lead $lead, string $icon, ?string $body = null): void
    {
        Notification::make()
            ->title($title)
            ->body(is_string($body) && $body !== '' ? mb_strimwidth($body, 0, 300, '…') : null)
            ->icon($icon)
            ->actions([Action::make('open')->label('Open enquiry')->url(LeadResource::getUrl('view', ['record' => $lead]))])
            ->sendToDatabase($user);
    }
}
