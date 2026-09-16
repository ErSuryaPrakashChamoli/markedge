<?php

namespace App\Models;

use App\Models\Concerns\BumpsContentVersion;
use App\Models\Concerns\HasStandardImageConversions;
use App\Models\Concerns\RecordsActivity;
use Database\Factories\SeoMetaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\MediaLibrary\HasMedia;

/**
 * Per-entity SEO overrides. Every field is optional; the resolver falls back to
 * entity content and then to global defaults (architecture §15). No length limits.
 */
#[Table('seo_meta')]
#[Fillable([
    'seoable_type', 'seoable_id', 'title', 'description', 'canonical_url', 'robots_index', 'robots_follow',
    'og_title', 'og_description', 'twitter_title', 'twitter_description', 'schema_type', 'schema_overrides',
    'include_in_sitemap', 'sitemap_priority', 'sitemap_changefreq',
])]
class SeoMeta extends Model implements HasMedia
{
    /** @use HasFactory<SeoMetaFactory> */
    use BumpsContentVersion, HasFactory, HasStandardImageConversions, RecordsActivity;

    /** @var array<int, string> */
    protected array $activityLogAttributes = ['title', 'description', 'canonical_url', 'robots_index', 'robots_follow', 'schema_type', 'include_in_sitemap'];

    protected function casts(): array
    {
        return [
            'robots_index' => 'boolean',
            'robots_follow' => 'boolean',
            'schema_overrides' => 'array',
            'include_in_sitemap' => 'boolean',
            'sitemap_priority' => 'decimal:1',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('og_image')->singleFile();
        $this->addMediaCollection('twitter_image')->singleFile();
    }

    public function seoable(): MorphTo
    {
        return $this->morphTo();
    }

    public function robotsDirective(): string
    {
        return ($this->robots_index ? 'index' : 'noindex').', '.($this->robots_follow ? 'follow' : 'nofollow');
    }
}
