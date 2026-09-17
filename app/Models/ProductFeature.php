<?php

namespace App\Models;

use App\Models\Concerns\BumpsContentVersion;
use App\Models\Concerns\HasSortOrder;
use App\Models\Concerns\HasStandardImageConversions;
use Database\Factories\ProductFeatureFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;

#[Fillable(['product_id', 'product_module_id', 'title', 'description', 'icon', 'group_label', 'sort_order'])]
class ProductFeature extends Model implements HasMedia
{
    /** @use HasFactory<ProductFeatureFactory> */
    use BumpsContentVersion, HasFactory, HasSortOrder, HasStandardImageConversions;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')->singleFile();
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(ProductModule::class, 'product_module_id');
    }

    public function capabilities(): HasMany
    {
        return $this->hasMany(ProductCapability::class)->orderBy('sort_order')->orderBy('id');
    }
}
