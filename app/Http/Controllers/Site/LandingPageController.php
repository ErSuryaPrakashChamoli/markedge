<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Services\Cms\ContentResolver;
use App\Services\Cms\PageRenderer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class LandingPageController extends Controller
{
    public function __invoke(ContentResolver $content, PageRenderer $renderer, string $slug): View|RedirectResponse
    {
        $landingPage = $content->landingPage($slug) ?? abort(404);

        if ($landingPage->isExpired()) {
            return redirect($landingPage->expired_redirect_url ?: '/', 302);
        }

        return $renderer->render($landingPage);
    }
}
