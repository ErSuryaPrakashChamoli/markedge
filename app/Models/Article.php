<?php

namespace App\Models;

use App\Models\Concerns\BumpsContentVersion;
use App\Models\Concerns\HasFaqs;
use App\Models\Concerns\HasSeo;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\HasStandardImageConversions;
use App\Models\Concerns\Publishable;
use App\Models\Concerns\RecordsActivity;
use App\Models\Concerns\TracksAuthorship;
use Database\Factories\ArticleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;

#[Fillable([
    'title', 'slug', 'excerpt', 'body', 'author_id', 'article_category_id', 'is_featured',
    'cta_id', 'status', 'published_at',
])]
class Article extends Model implements HasMedia
{
    /** @use HasFactory<ArticleFactory> */
    use BumpsContentVersion, HasFactory, HasFaqs, HasSeo, HasSlug, HasStandardImageConversions,
        Publishable, RecordsActivity, SoftDeletes, TracksAuthorship;

    public const int WORDS_PER_MINUTE = 200;

    /** @var array<int, string> */
    protected array $activityLogAttributes = ['title', 'slug', 'status', 'published_at', 'author_id', 'article_category_id', 'is_featured'];

    protected static function booted(): void
    {
        static::saving(function (Article $article): void {
            $article->reading_time_minutes = static::estimateReadingTime((string) $article->body);
        });
    }

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
        ];
    }

    protected function slugSource(): string
    {
        return 'title';
    }

    public static function estimateReadingTime(string $html): int
    {
        $words = str_word_count(strip_tags($html));

        return max(1, (int) ceil($words / self::WORDS_PER_MINUTE));
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('featured')->singleFile();
    }

    #[Scope]
    protected function featured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ArticleCategory::class, 'article_category_id');
    }

    public function cta(): BelongsTo
    {
        return $this->belongsTo(Cta::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function services(): MorphToMany
    {
        return $this->linked(Service::class);
    }

    public function products(): MorphToMany
    {
        return $this->linked(Product::class);
    }

    public function industries(): MorphToMany
    {
        return $this->linked(Industry::class);
    }

    public function solutions(): MorphToMany
    {
        return $this->linked(Solution::class);
    }

    public function relatedArticles(): MorphToMany
    {
        return $this->linked(Article::class);
    }

    /**
     * @param  class-string<Model>  $related
     */
    protected function linked(string $related): MorphToMany
    {
        return $this->morphedByMany($related, 'linkable', 'article_links', 'article_id', 'linkable_id')
            ->withPivot('sort_order')
            ->orderByPivot('sort_order');
    }
}
