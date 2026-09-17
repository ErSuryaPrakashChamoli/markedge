<?php

namespace App\Models;

use App\Models\Concerns\BumpsContentVersion;
use App\Models\Concerns\HasSortOrder;
use Database\Factories\ProductCapabilityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The finest grain of the product hierarchy: Product → Module → Feature → Capability.
 */
#[Fillable(['product_feature_id', 'name', 'description', 'sort_order'])]
class ProductCapability extends Model
{
    /** @use HasFactory<ProductCapabilityFactory> */
    use BumpsContentVersion, HasFactory, HasSortOrder;

    public function feature(): BelongsTo
    {
        return $this->belongsTo(ProductFeature::class, 'product_feature_id');
    }
}
