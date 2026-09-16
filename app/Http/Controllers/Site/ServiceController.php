<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Seo\MetaResolver;
use App\Services\Cms\ContentResolver;
use App\Services\Cms\CtaResolver;
use App\Services\Cms\PageRenderer;
use Illuminate\Contracts\View\View;

class ServiceController extends Controller
{
    public function index(ContentResolver $content, MetaResolver $meta, CtaResolver $ctas): View
    {
        return view('pages.services.index', [
            'categories' => $content->serviceCategories(),
            'meta' => $meta->forListing('Services', 'Software development, IT infrastructure and digital growth services from Markedge Technologies.', '/services'),
            'cta' => $ctas->fromSetting('cta.default_service') ?? $ctas->fromSetting('cta.default'),
        ]);
    }

    /**
     * /services/{slug} resolves a category first, then a service (shared slug namespace, architecture §10.3).
     */
    public function show(ContentResolver $content, PageRenderer $renderer, string $slug): View
    {
        $entity = $content->serviceCategory($slug) ?? $content->service($slug) ?? abort(404);

        return $renderer->render($entity);
    }
}
