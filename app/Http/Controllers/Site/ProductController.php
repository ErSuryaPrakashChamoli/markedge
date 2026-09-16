<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Seo\SeoEngine;
use App\Services\Cms\ContentResolver;
use App\Services\Cms\CtaResolver;
use App\Services\Cms\PageRenderer;
use Illuminate\Contracts\View\View;

class ProductController extends Controller
{
    public function index(ContentResolver $content, SeoEngine $meta, CtaResolver $ctas): View
    {
        return view('pages.products.index', [
            'products' => $content->products(),
            'meta' => $meta->forListing('Products', 'Business platforms built by Markedge Technologies.', '/products', breadcrumbs: [['label' => 'Products', 'url' => null]]),
            'cta' => $ctas->fromSetting('cta.default_product') ?? $ctas->fromSetting('cta.default'),
        ]);
    }

    public function show(ContentResolver $content, PageRenderer $renderer, string $slug): View
    {
        return $renderer->render($content->product($slug) ?? abort(404));
    }
}
