<?php

namespace App\Http\Controllers;

use App\Services\Cms\PreviewLink;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class PreviewController extends Controller
{
    public function __invoke(Request $request, PreviewLink $links, string $type, int $id): SymfonyResponse
    {
        $record = $links->resolve($type, $id);

        abort_if($record === null, 404);

        return response()
            ->view('preview.show', ['record' => $record, 'type' => $type, 'previewedBy' => $request->integer('by') ?: null])
            ->header('X-Robots-Tag', 'noindex, nofollow, noarchive')
            ->header('Cache-Control', 'no-store, private');
    }
}
