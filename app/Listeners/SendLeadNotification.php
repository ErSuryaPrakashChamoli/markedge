<?php

namespace App\Listeners;

use App\Events\LeadCreated;
use App\Mail\NewLeadNotification;
use App\Models\Lead;
use App\Services\Cms\Settings;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Emails the team about a new lead when recipients are configured on the form or in settings.
 * Any failure is reported and swallowed: the lead is already stored.
 */
class SendLeadNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [30, 120, 600];

    public int $timeout = 60;

    public function __construct(private readonly Settings $settings) {}

    public function handle(LeadCreated $event): void
    {
        if ($event->duplicate) {
            return;
        }

        $lead = Lead::query()->with(['form', 'service', 'product'])->find($event->leadId);

        if ($lead === null) {
            return;
        }

        $recipients = collect($lead->form?->notify_emails ?: $this->settings->get('leads.notify_emails', []))
            ->filter(fn ($email) => is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values();

        if ($recipients->isEmpty()) {
            return;
        }

        try {
            Mail::to($recipients->all())->send(new NewLeadNotification($lead));
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
