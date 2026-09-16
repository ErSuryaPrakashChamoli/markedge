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
use App\Models\Concerns\HasTechnologies;
use App\Models\Concerns\Publishable;
use App\Models\Concerns\RecordsActivity;
use App\Models\Concerns\TracksAuthorship;
use Database\Factories\ServiceFactory;
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
    'service_category_id', 'name', 'slug', 'tagline', 'short_description', 'overview',
    'benefits', 'features', 'process', 'deliverables', 'blocks', 'cta_id',
    'is_featured', 'sort_order', 'status', 'published_at',
])]
class Service extends Model implements HasMedia
{
    /** @use HasFactory<ServiceFactory> */
    use BumpsContentVersion, HasBlocks, HasEditorialWorkflow, HasFactory, HasFaqs, HasSeo, HasSlug, HasSortOrder,
        HasStandardImageConversions, HasTechnologies, Publishable, RecordsActivity,
        SoftDeletes, TracksAuthorship;

    /** @var array<int, string> */
    protected array $activityLogAttributes = ['name', 'slug', 'service_category_id', 'status', 'published_at', 'is_featured', 'sort_order'];

    protected function casts(): array
    {
        return [
            'benefits' => 'array',
            'features' => 'array',
            'process' => 'array',
            'deliverables' => 'array',
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

    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id');
    }

    public function cta(): BelongsTo
    {
        return $this->belongsTo(Cta::class);
    }

    public function relatedServices(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'service_service', 'service_id', 'related_service_id')
            ->withPivot('sort_order')
            ->orderByPivot('sort_order');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)->withPivot('sort_order')->orderByPivot('sort_order');
    }

    public function industries(): BelongsToMany
    {
        return $this->belongsToMany(Industry::class)->withPivot('sort_order')->orderByPivot('sort_order');
    }

    public function solutions(): BelongsToMany
    {
        return $this->belongsToMany(Solution::class)->withPivot('sort_order')->orderByPivot('sort_order');
    }

    public function caseStudies(): BelongsToMany
    {
        return $this->belongsToMany(CaseStudy::class)->withPivot('sort_order')->orderByPivot('sort_order');
    }

    public function articles(): MorphToMany
    {
        return $this->morphToMany(Article::class, 'linkable', 'article_links', 'linkable_id', 'article_id')
            ->withPivot('sort_order');
    }

    public function testimonials(): HasMany
    {
        return $this->hasMany(Testimonial::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }
}
