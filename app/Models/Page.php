<?php

namespace App\Models;

use App\Enums\PageTemplate;
use App\Models\Concerns\BumpsContentVersion;
use App\Models\Concerns\HasBlocks;
use App\Models\Concerns\HasFaqs;
use App\Models\Concerns\HasSeo;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\HasStandardImageConversions;
use App\Models\Concerns\Publishable;
use App\Models\Concerns\RecordsActivity;
use App\Models\Concerns\TracksAuthorship;
use Database\Factories\PageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;

/**
 * Block-composed CMS page served by the /{slug} catch-all. The home page is the page with slug "home".
 */
#[Fillable(['title', 'slug', 'template', 'excerpt', 'blocks', 'form_id', 'cta_id', 'status', 'published_at'])]
class Page extends Model implements HasMedia
{
    /** @use HasFactory<PageFactory> */
    use BumpsContentVersion, HasBlocks, HasFactory, HasFaqs, HasSeo, HasSlug, HasStandardImageConversions,
        Publishable, RecordsActivity, SoftDeletes, TracksAuthorship;

    public const string HOME_SLUG = 'home';

    /** @var array<int, string> */
    protected array $activityLogAttributes = ['title', 'slug', 'template', 'status', 'published_at'];

    protected function casts(): array
    {
        return [
            'template' => PageTemplate::class,
        ];
    }

    protected function slugSource(): string
    {
        return 'title';
    }

    protected function slugIsReserved(string $slug): bool
    {
        return in_array($slug, config('markedge.reserved_slugs', []), true);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('blocks');
    }

    #[Scope]
    protected function withTemplate(Builder $query, PageTemplate $template): Builder
    {
        return $query->where('template', $template);
    }

    public function isHome(): bool
    {
        return $this->slug === self::HOME_SLUG;
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    public function cta(): BelongsTo
    {
        return $this->belongsTo(Cta::class);
    }
}
