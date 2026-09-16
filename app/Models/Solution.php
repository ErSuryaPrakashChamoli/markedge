<?php

namespace App\Models;

use App\Models\Concerns\BumpsContentVersion;
use App\Models\Concerns\HasBlocks;
use App\Models\Concerns\HasFaqs;
use App\Models\Concerns\HasSeo;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\HasSortOrder;
use App\Models\Concerns\HasStandardImageConversions;
use App\Models\Concerns\HasTechnologies;
use App\Models\Concerns\Publishable;
use App\Models\Concerns\RecordsActivity;
use App\Models\Concerns\TracksAuthorship;
use Database\Factories\SolutionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;

#[Fillable([
    'name', 'slug', 'tagline', 'short_description', 'problem_statement', 'approach', 'outcomes',
    'blocks', 'cta_id', 'is_featured', 'sort_order', 'status', 'published_at',
])]
class Solution extends Model implements HasMedia
{
    /** @use HasFactory<SolutionFactory> */
    use BumpsContentVersion, HasBlocks, HasFactory, HasFaqs, HasSeo, HasSlug, HasSortOrder,
        HasStandardImageConversions, HasTechnologies, Publishable, RecordsActivity,
        SoftDeletes, TracksAuthorship;

    /** @var array<int, string> */
    protected array $activityLogAttributes = ['name', 'slug', 'status', 'published_at', 'is_featured', 'sort_order'];

    protected function casts(): array
    {
        return [
            'outcomes' => 'array',
            'is_featured' => 'boolean',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('hero')->singleFile();
        $this->addMediaCollection('blocks');
    }

    #[Scope]
    protected function featured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function cta(): BelongsTo
    {
        return $this->belongsTo(Cta::class);
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class)->withPivot('sort_order')->orderByPivot('sort_order');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)->withPivot('sort_order')->orderByPivot('sort_order');
    }

    public function industries(): BelongsToMany
    {
        return $this->belongsToMany(Industry::class)->withPivot('sort_order')->orderByPivot('sort_order');
    }

    public function articles(): MorphToMany
    {
        return $this->morphToMany(Article::class, 'linkable', 'article_links', 'linkable_id', 'article_id')
            ->withPivot('sort_order');
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }
}
