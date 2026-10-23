<?php

namespace App\Models;

use App\Enums\SocialPlatform;
use App\Models\Concerns\BumpsContentVersion;
use App\Models\Concerns\HasSortOrder;
use App\Models\Concerns\HasStandardImageConversions;
use App\Models\Concerns\RecordsActivity;
use Database\Factories\SocialVideoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;

/**
 * A video Markedge posted on a social platform (Instagram, LinkedIn, YouTube…), listed in the
 * Resources section. The post itself stays on the platform: the site plays it through the
 * platform's own embed player when one exists, and links out otherwise.
 */
#[Fillable(['title', 'platform', 'url', 'caption', 'is_vertical', 'posted_at', 'is_featured', 'is_visible', 'sort_order'])]
class SocialVideo extends Model implements HasMedia
{
    /** @use HasFactory<SocialVideoFactory> */
    use BumpsContentVersion, HasFactory, HasSortOrder, HasStandardImageConversions, RecordsActivity, SoftDeletes;

    /** @var array<int, string> */
    protected array $activityLogAttributes = ['title', 'platform', 'url', 'is_visible', 'is_featured'];

    protected function casts(): array
    {
        return [
            'platform' => SocialPlatform::class,
            'is_vertical' => 'boolean',
            'posted_at' => 'date',
            'is_featured' => 'boolean',
            'is_visible' => 'boolean',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('featured')->singleFile();
    }

    #[Scope]
    protected function visible(Builder $query): Builder
    {
        return $query->where('is_visible', true);
    }

    #[Scope]
    protected function featured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    /**
     * Featured first, then the admin's order, then the newest post.
     */
    #[Scope]
    protected function forDisplay(Builder $query): Builder
    {
        return $query->orderByDesc('is_featured')->orderBy('sort_order')->orderByDesc('posted_at')->orderByDesc('id');
    }

    public function embedUrl(): ?string
    {
        return $this->platform?->embedUrl((string) $this->url);
    }
}
