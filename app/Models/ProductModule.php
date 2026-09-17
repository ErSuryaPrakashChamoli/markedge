<?php

namespace App\Models;

use App\Models\Concerns\BumpsContentVersion;
use App\Models\Concerns\HasSortOrder;
use App\Models\Concerns\HasStandardImageConversions;
use Database\Factories\ProductModuleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;

#[Fillable(['product_id', 'name', 'summary', 'description', 'highlights', 'sort_order'])]
class ProductModule extends Model implements HasMedia
{
    /** @use HasFactory<ProductModuleFactory> */
    use BumpsContentVersion, HasFactory, HasSortOrder, HasStandardImageConversions;

    protected function casts(): array
    {
        return [
            'highlights' => 'array',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')->singleFile();
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function features(): HasMany
    {
        return $this->hasMany(ProductFeature::class)->orderBy('sort_order')->orderBy('id');
    }
}
