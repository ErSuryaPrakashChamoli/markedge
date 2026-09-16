<?php

namespace App\Models;

use App\Models\Concerns\BumpsContentVersion;
use App\Models\Concerns\HasBlocks;
use App\Models\Concerns\HasFaqs;
use App\Models\Concerns\HasSeo;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\HasStandardImageConversions;
use App\Models\Concerns\Publishable;
use App\Models\Concerns\RecordsActivity;
use App\Models\Concerns\TracksAuthorship;
use Database\Factories\LandingPageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\MessageBag;
use Spatie\MediaLibrary\HasMedia;

#[Fillable([
    'title', 'slug', 'campaign_id', 'form_id', 'cta_id', 'blocks', 'hide_navigation', 'hide_footer_links',
    'tracking', 'status', 'published_at', 'expires_at', 'expired_redirect_url',
])]
class LandingPage extends Model implements HasMedia
{
    /** @use HasFactory<LandingPageFactory> */
    use BumpsContentVersion, HasBlocks, HasFactory, HasFaqs, HasSeo, HasSlug, HasStandardImageConversions,
        Publishable, RecordsActivity, SoftDeletes, TracksAuthorship;

    /** @var array<int, string> */
    protected array $activityLogAttributes = ['title', 'slug', 'campaign_id', 'form_id', 'status', 'published_at', 'expires_at'];

    protected function casts(): array
    {
        return [
            'hide_navigation' => 'boolean',
            'hide_footer_links' => 'boolean',
            'tracking' => 'array',
            'expires_at' => 'datetime',
        ];
    }

    protected function slugSource(): string
    {
        return 'title';
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('blocks');
    }

    /**
     * A landing page must be able to convert: it needs a form or a CTA (architecture §35).
     */
    public function publishChecklist(): MessageBag
    {
        $errors = new MessageBag;

        if ($this->form_id === null && $this->cta_id === null) {
            $errors->add('form_id', 'A landing page needs a form or a call to action before it can be published.');
        }

        return $errors;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
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
}
