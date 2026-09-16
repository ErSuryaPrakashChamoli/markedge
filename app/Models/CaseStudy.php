<?php

namespace App\Models;

use App\Models\Concerns\BumpsContentVersion;
use App\Models\Concerns\HasBlocks;
use App\Models\Concerns\HasEditorialWorkflow;
use App\Models\Concerns\HasSeo;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\HasSortOrder;
use App\Models\Concerns\HasStandardImageConversions;
use App\Models\Concerns\HasTechnologies;
use App\Models\Concerns\Publishable;
use App\Models\Concerns\RecordsActivity;
use App\Models\Concerns\TracksAuthorship;
use Database\Factories\CaseStudyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\MessageBag;
use Spatie\MediaLibrary\HasMedia;

/**
 * Structured case study. Outcomes may be qualitative or quantitative and are shown exactly as entered.
 */
#[Fillable([
    'client_id', 'industry_id', 'title', 'slug', 'excerpt', 'challenge', 'solution', 'implementation',
    'results', 'outcomes', 'blocks', 'cta_id', 'is_featured', 'sort_order', 'status', 'published_at',
])]
class CaseStudy extends Model implements HasMedia
{
    /** @use HasFactory<CaseStudyFactory> */
    use BumpsContentVersion, HasBlocks, HasEditorialWorkflow, HasFactory, HasSeo, HasSlug, HasSortOrder,
        HasStandardImageConversions, HasTechnologies, Publishable, RecordsActivity,
        SoftDeletes, TracksAuthorship;

    /** @var array<int, string> */
    protected array $activityLogAttributes = ['title', 'slug', 'client_id', 'industry_id', 'status', 'published_at', 'is_featured'];

    protected function casts(): array
    {
        return [
            'outcomes' => 'array',
            'is_featured' => 'boolean',
        ];
    }

    protected function slugSource(): string
    {
        return 'title';
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('hero')->singleFile();
        $this->addMediaCollection('gallery');
        $this->addMediaCollection('blocks');
    }

    #[Scope]
    protected function featured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    /**
     * Problems that block publishing (architecture §13).
     */
    public function publishChecklist(): MessageBag
    {
        $errors = new MessageBag;

        if (blank(strip_tags((string) $this->challenge))) {
            $errors->add('challenge', 'A case study needs a challenge before it can be published.');
        }

        if (blank(strip_tags((string) $this->solution))) {
            $errors->add('solution', 'A case study needs a solution before it can be published.');
        }

        return $errors;
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function industry(): BelongsTo
    {
        return $this->belongsTo(Industry::class);
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
}
