<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Products\ProductComparison;
use App\Seo\SeoEngine;
use App\Services\Cms\CtaResolver;
use Illuminate\Contracts\View\View;

class ProductCompareController extends Controller
{
    public function __invoke(ProductComparison $comparison, SeoEngine $meta, CtaResolver $ctas): View
    {
        $matrix = $comparison->build() ?? abort(404);

        return view('pages.products.compare', $matrix + [
            'meta' => $meta->forListing('Compare products', 'Modules and features of Markedge products side by side, exactly as documented for each product.', '/products/compare', breadcrumbs: [['label' => 'Products', 'url' => '/products'], ['label' => 'Compare', 'url' => null]]),
            'cta' => $ctas->fromSetting('cta.default_product') ?? $ctas->fromSetting('cta.default'),
        ]);
    }
}
