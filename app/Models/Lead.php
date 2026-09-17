<?php

namespace App\Models;

use App\Enums\LeadPriority;
use App\Enums\LeadStatus;
use App\Models\Concerns\RecordsActivity;
use Database\Factories\LeadFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * The single conversion record. Attribution is a snapshot copied from the visitor
 * cookie at submission time so it survives campaign edits (architecture §18, §20).
 */
#[Fillable([
    'name', 'company', 'email', 'phone', 'country', 'city', 'requirement', 'message',
    'form_id', 'landing_page_id', 'campaign_id', 'service_id', 'product_id', 'industry_id', 'solution_id', 'cta_id',
    'submitted_from_url', 'status', 'assigned_to', 'priority', 'team', 'lost_reason', 'deal_value', 'qualification',
    'stage_entered_at', 'next_follow_up_at', 'last_activity_at', 'custom_fields',
    'first_source', 'first_medium', 'first_campaign', 'first_term', 'first_content', 'first_referrer', 'first_landing_page', 'first_visited_at',
    'last_source', 'last_medium', 'last_campaign', 'last_term', 'last_content', 'last_referrer', 'last_landing_page', 'last_visited_at',
    'visitor_id', 'device_type', 'browser', 'os', 'ip', 'user_agent', 'locale', 'consent_given_at', 'consent_text', 'submission_token',
    'duplicate_of_lead_id', 'spam_score', 'notes', 'contacted_at', 'closed_at',
])]
class Lead extends Model
{
    /** @use HasFactory<LeadFactory> */
    use HasFactory, RecordsActivity, SoftDeletes;

    /** Only workflow fields are audited; personal data never enters the activity log. */
    protected array $activityLogAttributes = ['status', 'assigned_to', 'priority', 'team', 'lost_reason', 'duplicate_of_lead_id', 'spam_score'];

    protected function casts(): array
    {
        return [
            'status' => LeadStatus::class,
            'priority' => LeadPriority::class,
            'deal_value' => 'decimal:2',
            'qualification' => 'array',
            'stage_entered_at' => 'datetime',
            'next_follow_up_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'custom_fields' => 'array',
            'first_visited_at' => 'datetime',
            'last_visited_at' => 'datetime',
            'consent_given_at' => 'datetime',
            'contacted_at' => 'datetime',
            'closed_at' => 'datetime',
            'spam_score' => 'integer',
        ];
    }

    #[Scope]
    protected function open(Builder $query): Builder
    {
        return $query->whereIn('status', LeadStatus::pipeline());
    }

    #[Scope]
    protected function closed(Builder $query): Builder
    {
        return $query->whereIn('status', LeadStatus::closed());
    }

    #[Scope]
    protected function ownedBy(Builder $query, int $userId): Builder
    {
        return $query->where('assigned_to', $userId);
    }

    #[Scope]
    protected function followUpOverdue(Builder $query): Builder
    {
        return $query->whereNotNull('next_follow_up_at')->where('next_follow_up_at', '<', now());
    }

    /**
     * Hours since the enquiry arrived without a first contact, or null once contacted.
     */
    public function hoursAwaitingContact(): ?float
    {
        if ($this->contacted_at !== null || ! $this->status?->isOpen()) {
            return null;
        }

        return round($this->created_at->diffInMinutes(now()) / 60, 1);
    }

    public function breachesFirstContactSla(): bool
    {
        $hours = config('markedge.sales.sla.first_contact_hours');

        return $hours !== null && ($this->hoursAwaitingContact() ?? 0) > (int) $hours;
    }

    #[Scope]
    protected function notSpam(Builder $query): Builder
    {
        return $query->where('status', '!=', LeadStatus::Spam);
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    public function landingPage(): BelongsTo
    {
        return $this->belongsTo(LandingPage::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function industry(): BelongsTo
    {
        return $this->belongsTo(Industry::class);
    }

    public function solution(): BelongsTo
    {
        return $this->belongsTo(Solution::class);
    }

    public function cta(): BelongsTo
    {
        return $this->belongsTo(Cta::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function duplicateOf(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'duplicate_of_lead_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(ConversionEvent::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(LeadActivity::class)->latest('id');
    }

    public function followUps(): HasMany
    {
        return $this->hasMany(LeadFollowUp::class)->orderBy('due_at');
    }
}
