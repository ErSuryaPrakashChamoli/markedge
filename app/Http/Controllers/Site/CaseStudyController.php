<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Seo\MetaResolver;
use App\Services\Cms\ContentResolver;
use App\Services\Cms\CtaResolver;
use App\Services\Cms\PageRenderer;
use Illuminate\Contracts\View\View;

class CaseStudyController extends Controller
{
    public function index(ContentResolver $content, MetaResolver $meta, CtaResolver $ctas): View
    {
        return view('pages.case-studies.index', [
            'caseStudies' => $content->caseStudies(),
            'meta' => $meta->forListing('Case Studies', 'Selected work by Markedge Technologies.', '/case-studies'),
            'cta' => $ctas->fromSetting('cta.default'),
        ]);
    }

    public function show(ContentResolver $content, PageRenderer $renderer, string $slug): View
    {
        return $renderer->render($content->caseStudy($slug) ?? abort(404));
    }
}
