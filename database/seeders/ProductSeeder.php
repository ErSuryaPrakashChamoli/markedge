<?php

namespace Database\Seeders;

use App\Enums\ProductStatus;
use App\Models\Product;
use Illuminate\Database\Seeder;

/**
 * Markedge's current products by name and tagline only. Content is added in the admin.
 */
class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            'lead-management-system' => ['name' => 'Lead Management System', 'tagline' => 'Power every lead.'],
            'recruitment-management-system' => ['name' => 'Recruitment Management System', 'tagline' => 'Recruitment, organised around performance.'],
        ];

        $sort = 0;

        foreach ($products as $slug => $attributes) {
            $product = Product::query()->withTrashed()->firstOrNew(['slug' => $slug]);
            $product->fill($attributes + ['product_type' => 'Business platform', 'sort_order' => $sort++]);
            $product->status ??= ProductStatus::Draft;
            $product->save();
        }
    }
}
