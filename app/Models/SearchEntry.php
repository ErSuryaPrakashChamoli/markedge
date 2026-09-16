<?php

namespace App\Models;

use Database\Factories\SearchEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Denormalised search index row. Maintained by the search engine implementation (architecture §33).
 */
#[Fillable(['searchable_type', 'searchable_id', 'kind', 'category_slug', 'category_label', 'title', 'summary', 'body_text', 'keywords', 'weight', 'url', 'published_at'])]
class SearchEntry extends Model
{
    /** @use HasFactory<SearchEntryFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'weight' => 'integer',
        ];
    }

    public function searchable(): MorphTo
    {
        return $this->morphTo();
    }
}
