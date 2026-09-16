<?php

namespace App\Models;

use App\Enums\CampaignChannel;
use App\Enums\CampaignStatus;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\RecordsActivity;
use Database\Factories\CampaignFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'name', 'slug', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'channel',
    'status', 'starts_at', 'ends_at', 'landing_page_id', 'form_id', 'cta_id', 'notes', 'tracking',
])]
class Campaign extends Model
{
    /** @use HasFactory<CampaignFactory> */
    use HasFactory, HasSlug, RecordsActivity, SoftDeletes;

    /** @var array<int, string> */
    protected array $activityLogAttributes = ['name', 'utm_campaign', 'status', 'starts_at', 'ends_at', 'landing_page_id'];

    protected function casts(): array
    {
        return [
            'channel' => CampaignChannel::class,
            'status' => CampaignStatus::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'tracking' => 'array',
        ];
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('status', CampaignStatus::Active);
    }

    #[Scope]
    protected function matchingUtmCampaign(Builder $query, string $utmCampaign): Builder
    {
        return $query->whereRaw('LOWER(utm_campaign) = ?', [mb_strtolower($utmCampaign)]);
    }

    public function isRunning(): bool
    {
        return $this->status === CampaignStatus::Active
            && ($this->starts_at === null || $this->starts_at->isPast())
            && ($this->ends_at === null || $this->ends_at->isFuture());
    }

    public function landingPages(): HasMany
    {
        return $this->hasMany(LandingPage::class);
    }

    public function defaultLandingPage(): BelongsTo
    {
        return $this->belongsTo(LandingPage::class, 'landing_page_id');
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    public function cta(): BelongsTo
    {
        return $this->belongsTo(Cta::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function ctaClicks(): HasMany
    {
        return $this->hasMany(CtaClick::class);
    }
}
