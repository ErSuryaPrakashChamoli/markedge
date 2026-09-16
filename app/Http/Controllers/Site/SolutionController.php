<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Seo\SeoEngine;
use App\Services\Cms\ContentResolver;
use App\Services\Cms\CtaResolver;
use App\Services\Cms\PageRenderer;
use Illuminate\Contracts\View\View;

class SolutionController extends Controller
{
    public function index(ContentResolver $content, SeoEngine $meta, CtaResolver $ctas): View
    {
        return view('pages.solutions.index', [
            'solutions' => $content->solutions(),
            'industries' => $content->industries(),
            'meta' => $meta->forListing('Solutions', 'Business problems Markedge Technologies helps solve with software, infrastructure and digital growth.', '/solutions', breadcrumbs: [['label' => 'Solutions', 'url' => null]]),
            'cta' => $ctas->fromSetting('cta.default'),
        ]);
    }

    public function show(ContentResolver $content, PageRenderer $renderer, string $slug): View
    {
        return $renderer->render($content->solution($slug) ?? abort(404));
    }
}
