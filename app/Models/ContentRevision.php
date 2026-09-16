<?php

namespace App\Models;

use Database\Factories\ContentRevisionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * An immutable snapshot of a content record (attributes, SEO, media references, relation ids).
 * Versions are deterministic per record (v1, v2, …) and enforced by a unique index.
 */
#[Fillable(['revisionable_type', 'revisionable_id', 'version', 'snapshot', 'checksum', 'reason', 'created_by', 'created_at'])]
class ContentRevision extends Model
{
    /** @use HasFactory<ContentRevisionFactory> */
    use HasFactory;

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'snapshot' => 'array',
            'version' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function revisionable(): MorphTo
    {
        return $this->morphTo();
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function label(): string
    {
        return 'v'.$this->version;
    }
}
