<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ServiceCategory;
use App\Seo\SeoEngine;
use App\Services\Cms\ContentResolver;
use App\Services\Cms\CtaResolver;
use App\Services\Cms\PageRenderer;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(ContentResolver $content, PageRenderer $renderer, SeoEngine $meta, CtaResolver $ctas): View
    {
        $home = $content->homePage();

        if ($home !== null) {
            return $renderer->render($home);
        }

        // No published home page yet: a structural, claim-free fallback built from real records.
        return view('pages.home-fallback', [
            'meta' => $meta->forListing(null, null, '/'),
            'categories' => ServiceCategory::query()->published()->ordered()->withCount(['services' => fn ($q) => $q->published()])->get(),
            'products' => Product::query()->publiclyVisible()->ordered()->with('media')->get(),
            'cta' => $ctas->fromSetting('cta.default'),
        ]);
    }
}
