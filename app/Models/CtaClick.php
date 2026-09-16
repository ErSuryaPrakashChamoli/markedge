<?php

namespace App\Models;

use Database\Factories\CtaClickFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['cta_id', 'action', 'path', 'campaign_id', 'utm', 'created_at'])]
class CtaClick extends Model
{
    /** @use HasFactory<CtaClickFactory> */
    use HasFactory;

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'utm' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function cta(): BelongsTo
    {
        return $this->belongsTo(Cta::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }
}
