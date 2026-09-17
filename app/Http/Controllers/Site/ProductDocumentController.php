<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Seo\SeoEngine;
use App\Services\Cms\ContentResolver;
use App\Services\Cms\CtaResolver;
use App\Services\Cms\PageRenderer;
use Illuminate\Contracts\View\View;

class ProductDocumentController extends Controller
{
    public function index(ContentResolver $content, SeoEngine $meta, CtaResolver $ctas, string $slug): View
    {
        $product = $content->product($slug) ?? abort(404);
        $documents = $content->productDocuments($product);

        abort_if($documents->isEmpty(), 404);

        return view('pages.products.docs.index', [
            'product' => $product,
            'sections' => $documents->groupBy(fn ($doc) => $doc->section ?: 'Documentation'),
            'meta' => $meta->forListing("{$product->name} documentation", "Documentation for {$product->name} by Markedge Technologies.", "/products/{$product->slug}/docs", breadcrumbs: [['label' => 'Products', 'url' => '/products'], ['label' => $product->name, 'url' => "/products/{$product->slug}"], ['label' => 'Documentation', 'url' => null]]),
            'cta' => $ctas->forEntity($product),
        ]);
    }

    public function show(ContentResolver $content, PageRenderer $renderer, string $slug, string $document): View
    {
        return $renderer->render($content->productDocument($slug, $document) ?? abort(404));
    }
}
