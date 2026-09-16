<?php

namespace App\Http\Controllers;

use App\Services\Cms\PageRenderer;
use App\Services\Cms\PreviewLink;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreviewController extends Controller
{
    public function __invoke(Request $request, PreviewLink $links, PageRenderer $renderer, string $type, int $id): Response
    {
        $record = $links->resolve($type, $id) ?? abort(404);

        return response($renderer->render($record, preview: true))
            ->header('X-Robots-Tag', 'noindex, nofollow, noarchive')
            ->header('Cache-Control', 'no-store, private');
    }
}
