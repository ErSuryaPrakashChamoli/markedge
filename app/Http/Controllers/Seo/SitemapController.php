<?php

namespace App\Http\Controllers\Seo;

use App\Http\Controllers\Controller;
use App\Seo\Sitemap\SitemapGenerator;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(SitemapGenerator $sitemap): Response
    {
        return response($sitemap->xml(), 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
            'X-Robots-Tag' => 'noindex',
        ]);
    }
}
