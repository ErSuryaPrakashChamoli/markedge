<?php

namespace App\Models;

use App\Models\Concerns\BumpsContentVersion;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\HasSortOrder;
use App\Models\Concerns\HasStandardImageConversions;
use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;

/**
 * A real client. Hidden until Markedge confirms the name may be shown.
 */
#[Fillable(['name', 'slug', 'website_url', 'industry_id', 'description', 'is_visible', 'show_in_logo_cloud', 'sort_order'])]
class Client extends Model implements HasMedia
{
    /** @use HasFactory<ClientFactory> */
    use BumpsContentVersion, HasFactory, HasSlug, HasSortOrder, HasStandardImageConversions, SoftDeletes;

    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
            'show_in_logo_cloud' => 'boolean',
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

    #[Scope]
    protected function inLogoCloud(Builder $query): Builder
    {
        return $query->visible()->where('show_in_logo_cloud', true);
    }

    public function industry(): BelongsTo
    {
        return $this->belongsTo(Industry::class);
    }

    public function caseStudies(): HasMany
    {
        return $this->hasMany(CaseStudy::class);
    }

    public function testimonials(): HasMany
    {
        return $this->hasMany(Testimonial::class);
    }
}
