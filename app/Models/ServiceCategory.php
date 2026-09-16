<?php

namespace App\Models;

use App\Models\Concerns\BumpsContentVersion;
use App\Models\Concerns\HasBlocks;
use App\Models\Concerns\HasEditorialWorkflow;
use App\Models\Concerns\HasFaqs;
use App\Models\Concerns\HasSeo;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\HasSortOrder;
use App\Models\Concerns\HasStandardImageConversions;
use App\Models\Concerns\Publishable;
use App\Models\Concerns\RecordsActivity;
use App\Models\Concerns\TracksAuthorship;
use Database\Factories\ServiceCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;

/**
 * A capability pillar (BUILD / OPERATE / GROW) with its own page at /services/{slug}.
 */
#[Fillable([
    'name', 'slug', 'pillar_label', 'tagline', 'short_description', 'description', 'icon',
    'blocks', 'cta_id', 'is_featured', 'sort_order', 'status', 'published_at',
])]
class ServiceCategory extends Model implements HasMedia
{
    /** @use HasFactory<ServiceCategoryFactory> */
    use BumpsContentVersion, HasBlocks, HasEditorialWorkflow, HasFactory, HasFaqs, HasSeo, HasSlug, HasSortOrder,
        HasStandardImageConversions, Publishable, RecordsActivity, SoftDeletes, TracksAuthorship;

    /** @var array<int, string> */
    protected array $activityLogAttributes = ['name', 'slug', 'status', 'published_at', 'is_featured', 'sort_order'];

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('hero')->singleFile();
        $this->addMediaCollection('blocks');
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class)->orderBy('sort_order')->orderBy('id');
    }

    public function cta(): BelongsTo
    {
        return $this->belongsTo(Cta::class);
    }
}
