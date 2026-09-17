<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\ProductResource;
use App\Models\Product;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Publicly visible products only: the API never exposes drafts or archived products.
 */
class ProductController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return ProductResource::collection(Product::query()->publiclyVisible()->ordered()->with(['modules.features.capabilities', 'features.capabilities'])->paginate(25));
    }

    public function show(string $slug): ProductResource
    {
        return new ProductResource(Product::query()->publiclyVisible()->where('slug', $slug)->with(['modules.features.capabilities', 'features.capabilities'])->firstOrFail());
    }
}
