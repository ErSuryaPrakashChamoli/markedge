<?php

namespace App\Models;

use App\Enums\ConversionEventType;
use App\Enums\ProductStatus;
use App\Models\Concerns\BumpsContentVersion;
use App\Models\Concerns\HasBlocks;
use App\Models\Concerns\HasFaqs;
use App\Models\Concerns\HasSeo;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\HasSortOrder;
use App\Models\Concerns\HasStandardImageConversions;
use App\Models\Concerns\HasTechnologies;
use App\Models\Concerns\RecordsActivity;
use App\Models\Concerns\TracksAuthorship;
use Database\Factories\ProductFactory;
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

/**
 * Generic product. LMS, RMS and every future product are rows of this model;
 * variation comes from features, modules, the flexible block slot and relations.
 */
#[Fillable([
    'name', 'slug', 'tagline', 'product_type', 'short_description', 'long_description', 'status',
    'benefits', 'use_cases', 'integrations', 'deployment', 'security', 'blocks', 'cta_id', 'demo_form_id', 'external_url',
    'is_featured', 'sort_order', 'published_at', 'launched_at',
])]
class Product extends Model implements HasMedia
{
    /** @use HasFactory<ProductFactory> */
    use BumpsContentVersion, HasBlocks, HasFactory, HasFaqs, HasSeo, HasSlug, HasSortOrder,
        HasStandardImageConversions, HasTechnologies, RecordsActivity, SoftDeletes, TracksAuthorship;

    /** @var array<int, string> */
    protected array $activityLogAttributes = ['name', 'slug', 'status', 'product_type', 'published_at', 'is_featured', 'sort_order', 'demo_form_id', 'cta_id'];

    protected function casts(): array
    {
        return [
            'status' => ProductStatus::class,
            'benefits' => 'array',
            'use_cases' => 'array',
            'integrations' => 'array',
            'deployment' => 'array',
            'security' => 'array',
            'is_featured' => 'boolean',
            'published_at' => 'datetime',
            'launched_at' => 'date',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')->singleFile();
        $this->addMediaCollection('hero')->singleFile();
        $this->addMediaCollection('screenshots');
        $this->addMediaCollection('gallery');
        $this->addMediaCollection('blocks');
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('status', ProductStatus::Active);
    }

    #[Scope]
    protected function publiclyVisible(Builder $query): Builder
    {
        return $query->whereIn('status', ProductStatus::publiclyVisible());
    }

    #[Scope]
    protected function featured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function isPubliclyVisible(): bool
    {
        return in_array($this->status, ProductStatus::publiclyVisible(), true);
    }

    public function isComingSoon(): bool
    {
        return $this->status === ProductStatus::ComingSoon;
    }

    public function features(): HasMany
    {
        return $this->hasMany(ProductFeature::class)->orderBy('sort_order')->orderBy('id');
    }

    public function modules(): HasMany
    {
        return $this->hasMany(ProductModule::class)->orderBy('sort_order')->orderBy('id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ProductDocument::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Phase 13 page views recorded against this product.
     */
    public function pageViews(): HasMany
    {
        return $this->hasMany(ConversionEvent::class, 'entity_id')
            ->where('entity_type', $this->getMorphClass())
            ->where('type', ConversionEventType::PageViewed->value);
    }

    /**
     * Features that are not attached to a module, grouped by their label.
     */
    public function ungroupedFeatures(): HasMany
    {
        return $this->features()->whereNull('product_module_id');
    }

    public function cta(): BelongsTo
    {
        return $this->belongsTo(Cta::class);
    }

    public function demoForm(): BelongsTo
    {
        return $this->belongsTo(Form::class, 'demo_form_id');
    }

    public function industries(): BelongsToMany
    {
        return $this->belongsToMany(Industry::class)->withPivot('sort_order')->orderByPivot('sort_order');
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class)->withPivot('sort_order')->orderByPivot('sort_order');
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
