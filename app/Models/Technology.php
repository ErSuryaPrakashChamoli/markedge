<?php

namespace App\Models;

use App\Enums\TechnologyCategory;
use App\Models\Concerns\BumpsContentVersion;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\HasSortOrder;
use App\Models\Concerns\HasStandardImageConversions;
use Database\Factories\TechnologyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Spatie\MediaLibrary\HasMedia;

#[Fillable(['name', 'slug', 'category', 'description', 'website_url', 'is_visible', 'sort_order'])]
class Technology extends Model implements HasMedia
{
    /** @use HasFactory<TechnologyFactory> */
    use BumpsContentVersion, HasFactory, HasSlug, HasSortOrder, HasStandardImageConversions;

    protected function casts(): array
    {
        return [
            'category' => TechnologyCategory::class,
            'is_visible' => 'boolean',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')->singleFile();
    }

    #[Scope]
    protected function visible(Builder $query): Builder
    {
        return $query->where('is_visible', true);
    }

    public function services(): MorphToMany
    {
        return $this->morphedByMany(Service::class, 'technologyable');
    }

    public function products(): MorphToMany
    {
        return $this->morphedByMany(Product::class, 'technologyable');
    }

    public function industries(): MorphToMany
    {
        return $this->morphedByMany(Industry::class, 'technologyable');
    }

    public function solutions(): MorphToMany
    {
        return $this->morphedByMany(Solution::class, 'technologyable');
    }

    public function caseStudies(): MorphToMany
    {
        return $this->morphedByMany(CaseStudy::class, 'technologyable');
    }
}
