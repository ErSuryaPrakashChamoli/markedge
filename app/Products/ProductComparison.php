<?php

namespace App\Products;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;

/**
 * Side-by-side view of publicly visible products built only from what each product stores:
 * modules and features by name. A blank cell means "not listed", never "not available".
 */
class ProductComparison
{
    public const int MINIMUM = 2;

    /**
     * @return array{products: Collection<int, Product>, modules: array<int, array{label: string, cells: array<int, bool>}>, features: array<int, array{label: string, cells: array<int, bool>}>, deployment: array<int, array<int, string>>}|null
     */
    public function build(): ?array
    {
        $products = Product::query()->publiclyVisible()->ordered()->with(['media', 'modules', 'features'])->get();

        if ($products->count() < self::MINIMUM) {
            return null;
        }

        return [
            'products' => $products,
            'modules' => $this->matrix($products, fn (Product $product) => $product->modules->pluck('name')),
            'features' => $this->matrix($products, fn (Product $product) => $product->features->pluck('title')),
            'deployment' => $products->map(fn (Product $product) => collect($product->deployment ?? [])->map(fn ($row) => is_array($row) ? trim((string) ($row['label'] ?? '')) : '')->filter()->values()->all())->all(),
        ];
    }

    /**
     * @param  Collection<int, Product>  $products
     * @return array<int, array{label: string, cells: array<int, bool>}>
     */
    protected function matrix(Collection $products, callable $names): array
    {
        $labels = [];

        foreach ($products as $index => $product) {
            foreach ($names($product) as $name) {
                $key = mb_strtolower(trim((string) $name));

                if ($key === '') {
                    continue;
                }

                $labels[$key] ??= ['label' => trim((string) $name), 'cells' => array_fill(0, $products->count(), false)];
                $labels[$key]['cells'][$index] = true;
            }
        }

        return array_values($labels);
    }
}
