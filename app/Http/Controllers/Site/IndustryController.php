<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Seo\MetaResolver;
use App\Services\Cms\ContentResolver;
use App\Services\Cms\CtaResolver;
use App\Services\Cms\PageRenderer;
use Illuminate\Contracts\View\View;

class IndustryController extends Controller
{
    public function index(ContentResolver $content, MetaResolver $meta, CtaResolver $ctas): View
    {
        return view('pages.industries.index', [
            'industries' => $content->industries(),
            'meta' => $meta->forListing('Industries', 'Technology for businesses across industries.', '/industries'),
            'cta' => $ctas->fromSetting('cta.default'),
        ]);
    }

    public function show(ContentResolver $content, PageRenderer $renderer, string $slug): View
    {
        return $renderer->render($content->industry($slug) ?? abort(404));
    }
}
