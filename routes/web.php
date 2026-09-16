<?php

use App\Http\Controllers\PreviewController;
use App\Http\Controllers\Seo\RobotsController;
use App\Http\Controllers\Seo\SitemapController;
use App\Http\Controllers\Site\CaseStudyController;
use App\Http\Controllers\Site\HomeController;
use App\Http\Controllers\Site\IndustryController;
use App\Http\Controllers\Site\InsightsController;
use App\Http\Controllers\Site\LandingPageController;
use App\Http\Controllers\Site\PageController;
use App\Http\Controllers\Site\ProductController;
use App\Http\Controllers\Site\SearchController;
use App\Http\Controllers\Site\ServiceController;
use App\Http\Controllers\Site\SolutionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public website (architecture §3.3)
|--------------------------------------------------------------------------
|
| Fixed routes first, entity sections next, the CMS page catch-all last.
| Every slug parameter is constrained to lowercase slugs.
|
*/

$slug = '[a-z0-9]+(?:-[a-z0-9]+)*';

Route::get('/', HomeController::class)->name('home');

Route::get('/search', SearchController::class)->middleware('throttle:search')->name('search');
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/robots.txt', RobotsController::class)->name('robots');

Route::get('/styleguide', function () {
    abort_unless(config('markedge.styleguide_enabled'), 404);

    return view('pages.styleguide');
})->name('styleguide');

Route::get('/preview/{type}/{id}', PreviewController::class)
    ->middleware(['signed', 'throttle:preview'])
    ->whereNumber('id')
    ->name('preview.show');

Route::get('/services', [ServiceController::class, 'index'])->name('services.index');
Route::get('/services/{slug}', [ServiceController::class, 'show'])->where('slug', $slug)->name('services.show');

Route::get('/solutions', [SolutionController::class, 'index'])->name('solutions.index');
Route::get('/solutions/{slug}', [SolutionController::class, 'show'])->where('slug', $slug)->name('solutions.show');

Route::get('/industries', [IndustryController::class, 'index'])->name('industries.index');
Route::get('/industries/{slug}', [IndustryController::class, 'show'])->where('slug', $slug)->name('industries.show');

Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{slug}', [ProductController::class, 'show'])->where('slug', $slug)->name('products.show');

Route::get('/case-studies', [CaseStudyController::class, 'index'])->name('case-studies.index');
Route::get('/case-studies/{slug}', [CaseStudyController::class, 'show'])->where('slug', $slug)->name('case-studies.show');

Route::get('/insights', [InsightsController::class, 'index'])->name('insights.index');
Route::get('/insights/category/{slug}', [InsightsController::class, 'category'])->where('slug', $slug)->name('insights.category');
Route::get('/insights/tag/{slug}', [InsightsController::class, 'tag'])->where('slug', $slug)->name('insights.tag');
Route::get('/insights/author/{slug}', [InsightsController::class, 'author'])->where('slug', $slug)->name('insights.author');
Route::get('/insights/{slug}', [InsightsController::class, 'show'])->where('slug', $slug)->name('insights.show');

Route::get('/lp/{slug}', LandingPageController::class)->where('slug', $slug)->name('landing.show');

// CMS pages: registered last so reserved sections above always win.
Route::get('/{slug}', PageController::class)->where('slug', $slug)->name('pages.show');
