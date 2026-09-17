<?php

namespace App\Http\Resources\V1;

use App\Models\Product;
use App\Models\ProductFeature;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Product
 */
class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $feature = fn (ProductFeature $f): array => ['id' => $f->id, 'title' => $f->title, 'description' => $f->description, 'capabilities' => $f->capabilities->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'description' => $c->description])->values()];

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'url' => url('/products/'.$this->slug),
            'tagline' => $this->tagline,
            'category' => $this->product_type,
            'status' => $this->status?->value,
            'short_description' => $this->short_description,
            'modules' => $this->whenLoaded('modules', fn () => $this->modules->map(fn ($m) => ['id' => $m->id, 'name' => $m->name, 'summary' => $m->summary, 'features' => $m->features->map($feature)->values()])->values()),
            'features' => $this->whenLoaded('features', fn () => $this->features->whereNull('product_module_id')->map($feature)->values()),
            'deployment' => $this->deployment ?? [],
            'security' => $this->security ?? [],
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
