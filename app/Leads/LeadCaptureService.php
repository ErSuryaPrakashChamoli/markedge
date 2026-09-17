<?php

namespace App\Leads;

use App\Analytics\Analytics;
use App\Enums\ConversionEventType;
use App\Enums\LeadStatus;
use App\Events\LeadCreated;
use App\Models\Campaign;
use App\Models\Lead;
use Illuminate\Support\Facades\DB;

/**
 * The single lead pipeline (architecture §18, Phase 8 §5–6). Every public form ends here.
 *
 * Idempotency: a submission token issued when the form was rendered is stored on the lead
 * (unique). A retry with the same token returns the existing lead and stores nothing.
 *
 * Duplicate policy: a lead from the same form with the same email (or phone when no email)
 * within markedge.leads.duplicate_window_hours is still stored, so no enquiry is lost, but
 * it is linked to the earlier lead through duplicate_of_lead_id and raises no notification.
 */
class LeadCaptureService
{
    public function __construct(private readonly Analytics $analytics) {}

    public function capture(LeadSubmission $submission): LeadCaptureResult
    {
        $existing = Lead::query()->where('submission_token', $submission->submissionToken)->first();

        if ($existing !== null) {
            return new LeadCaptureResult($existing, created: false);
        }

        $attribution = $submission->attribution;
        $first = $attribution->first;
        $last = $attribution->last;
        $campaign = $this->matchCampaign($last?->campaign, $first?->campaign);

        $result = DB::transaction(function () use ($submission, $attribution, $first, $last, $campaign): LeadCaptureResult {
            $duplicateOf = $this->findRecentDuplicate($submission);

            $lead = Lead::query()->create(array_filter($submission->core, fn ($value) => $value !== '' && $value !== null) + [
                'submission_token' => $submission->submissionToken,
                'form_id' => $submission->form->id,
                'landing_page_id' => $submission->relations['landing_page_id'] ?? null,
                'service_id' => $submission->relations['service_id'] ?? null,
                'product_id' => $submission->relations['product_id'] ?? null,
                'industry_id' => $submission->relations['industry_id'] ?? null,
                'solution_id' => $submission->relations['solution_id'] ?? null,
                'campaign_id' => $campaign?->id,
                'cta_id' => $attribution->creditedCtaId(),
                'custom_fields' => $submission->custom ?: null,
                'submitted_from_url' => $submission->conversionPath,
                'status' => LeadStatus::New,
                'first_source' => $first?->source,
                'first_medium' => $first?->medium,
                'first_campaign' => $first?->campaign,
                'first_term' => $first?->term,
                'first_content' => $first?->content,
                'first_referrer' => $first?->referrer,
                'first_landing_page' => $first?->landingPage,
                'first_visited_at' => $first?->at,
                'last_source' => $last?->source,
                'last_medium' => $last?->medium,
                'last_campaign' => $last?->campaign,
                'last_term' => $last?->term,
                'last_content' => $last?->content,
                'last_referrer' => $last?->referrer,
                'last_landing_page' => $last?->landingPage,
                'last_visited_at' => $last?->at,
                'visitor_id' => $attribution->visitorId,
                'consent_given_at' => $submission->consentGiven ? now() : null,
                'consent_text' => $submission->consentGiven ? $submission->form->consentStatement() : null,
                'user_agent' => $submission->userAgent,
                'ip' => $submission->ip,
                'locale' => $submission->locale,
                'duplicate_of_lead_id' => $duplicateOf?->id,
            ]);

            foreach ([ConversionEventType::FormSubmitted, ConversionEventType::LeadCreated] as $type) {
                $this->analytics->record($type, [
                    'lead_id' => $lead->id,
                    'form_id' => $submission->form->id,
                    'cta_id' => $lead->cta_id,
                    'campaign_id' => $campaign?->id,
                    'path' => $submission->conversionPath,
                    'entity_type' => $this->entityType($submission),
                    'entity_id' => $this->entityId($submission),
                ], null, $attribution);
            }

            return new LeadCaptureResult($lead, created: true, duplicate: $duplicateOf !== null);
        });

        // Notification hooks run after commit and never affect the stored lead.
        LeadCreated::dispatch($result->lead->id, $result->duplicate);

        return $result;
    }

    protected function matchCampaign(?string ...$utmCampaigns): ?Campaign
    {
        foreach (array_filter($utmCampaigns) as $utmCampaign) {
            $campaign = Campaign::query()->matchingUtmCampaign($utmCampaign)->first();

            if ($campaign !== null) {
                return $campaign;
            }
        }

        return null;
    }

    protected function findRecentDuplicate(LeadSubmission $submission): ?Lead
    {
        $email = $submission->core['email'] ?? null;
        $phone = $submission->core['phone'] ?? null;

        if (blank($email) && blank($phone)) {
            return null;
        }

        return Lead::query()
            ->where('form_id', $submission->form->id)
            ->where('created_at', '>=', now()->subHours((int) config('markedge.leads.duplicate_window_hours', 24)))
            ->when(filled($email), fn ($q) => $q->where('email', $email), fn ($q) => $q->where('phone', $phone))
            ->whereNull('duplicate_of_lead_id')
            ->latest('id')
            ->first();
    }

    protected function entityType(LeadSubmission $submission): ?string
    {
        return match (true) {
            filled($submission->relations['product_id'] ?? null) => 'product',
            filled($submission->relations['service_id'] ?? null) => 'service',
            filled($submission->relations['solution_id'] ?? null) => 'solution',
            filled($submission->relations['landing_page_id'] ?? null) => 'landing_page',
            default => null,
        };
    }

    protected function entityId(LeadSubmission $submission): ?int
    {
        return match ($this->entityType($submission)) {
            'product' => $submission->relations['product_id'],
            'service' => $submission->relations['service_id'],
            'solution' => $submission->relations['solution_id'],
            'landing_page' => $submission->relations['landing_page_id'],
            default => null,
        };
    }
}
