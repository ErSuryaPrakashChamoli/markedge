<?php

namespace App\Models;

use App\Models\Concerns\BumpsContentVersion;
use App\Models\Concerns\HasSeo;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\HasSortOrder;
use App\Models\Concerns\Publishable;
use App\Models\Concerns\RecordsActivity;
use App\Models\Concerns\TracksAuthorship;
use Database\Factories\ProductDocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Product documentation page (Phase 15). Public at /products/{product}/docs/{slug} only while
 * both the document is published and the product is publicly visible. Slugs are unique per product.
 */
#[Fillable(['product_id', 'title', 'slug', 'section', 'excerpt', 'body', 'sort_order', 'status', 'published_at'])]
class ProductDocument extends Model
{
    /** @use HasFactory<ProductDocumentFactory> */
    use BumpsContentVersion, HasFactory, HasSeo, HasSlug, HasSortOrder, Publishable, RecordsActivity, SoftDeletes, TracksAuthorship;

    /** @var array<int, string> */
    protected array $activityLogAttributes = ['title', 'slug', 'section', 'status', 'published_at', 'sort_order'];

    protected function slugSource(): string
    {
        return 'title';
    }

    protected function slugExists(string $slug): bool
    {
        $query = static::query()->withTrashed()->where('product_id', $this->product_id)->where('slug', $slug);

        if ($this->exists) {
            $query->whereKeyNot($this->getKey());
        }

        return $query->exists();
    }

    #[Scope]
    protected function publiclyAvailable(Builder $query): Builder
    {
        return $query->published()->whereHas('product', fn (Builder $q) => $q->publiclyVisible());
    }

    public function isPubliclyAvailable(): bool
    {
        return $this->isPublished() && ($this->product?->isPubliclyVisible() ?? false);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
