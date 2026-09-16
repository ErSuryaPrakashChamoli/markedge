<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Services\Cms\ContentResolver;
use App\Services\Cms\PageRenderer;
use Illuminate\Contracts\View\View;

class PageController extends Controller
{
    public function __invoke(ContentResolver $content, PageRenderer $renderer, string $slug): View
    {
        $page = $content->page($slug) ?? abort(404);

        return $renderer->render($page);
    }
}
