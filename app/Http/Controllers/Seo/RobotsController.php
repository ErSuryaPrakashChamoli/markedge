<?php

namespace App\Http\Controllers\Seo;

use App\Http\Controllers\Controller;
use App\Seo\RobotsBuilder;
use Illuminate\Http\Response;

class RobotsController extends Controller
{
    public function __invoke(RobotsBuilder $robots): Response
    {
        return response($robots->build(), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
