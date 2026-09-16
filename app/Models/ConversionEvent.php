<?php

namespace App\Models;

use App\Enums\ConversionEventType;
use Database\Factories\ConversionEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Minimal first-party measurement record. Never holds form answers, request bodies, headers or
 * personal data; the lead itself is the only place personal data lives.
 */
#[Fillable(['type', 'lead_id', 'form_id', 'cta_id', 'campaign_id', 'path', 'entity_type', 'entity_id', 'visitor_id', 'source', 'medium', 'campaign', 'meta', 'created_at'])]
class ConversionEvent extends Model
{
    /** @use HasFactory<ConversionEventFactory> */
    use HasFactory;

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'type' => ConversionEventType::class,
            'meta' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    public function cta(): BelongsTo
    {
        return $this->belongsTo(Cta::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function entity(): MorphTo
    {
        return $this->morphTo();
    }
}
