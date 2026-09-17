<?php

use App\Http\Controllers\Api\V1\AnalyticsController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\LeadController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Middleware\AuthenticateApiKey;
use Illuminate\Support\Facades\Route;

/*
| Versioned, key-authenticated API (Phase 16). Every route sits under /api/v1, is rate limited
| per key, and is scoped by the key's abilities. Writes go through the same services as the UI.
*/
Route::prefix('v1')->name('api.v1.')->middleware(['throttle:api'])->group(function (): void {
    Route::get('/health', HealthController::class)->name('health');

    Route::middleware([AuthenticateApiKey::class])->group(function (): void {
        Route::get('/products', [ProductController::class, 'index'])->middleware(AuthenticateApiKey::ability('products:read'))->name('products.index');
        Route::get('/products/{slug}', [ProductController::class, 'show'])->middleware(AuthenticateApiKey::ability('products:read'))->name('products.show');
        Route::get('/leads', [LeadController::class, 'index'])->middleware(AuthenticateApiKey::ability('leads:read'))->name('leads.index');
        Route::get('/leads/{lead}', [LeadController::class, 'show'])->middleware(AuthenticateApiKey::ability('leads:read'))->whereNumber('lead')->name('leads.show');
        Route::post('/leads', [LeadController::class, 'store'])->middleware(AuthenticateApiKey::ability('leads:write'))->name('leads.store');
        Route::get('/analytics/summary', AnalyticsController::class)->middleware(AuthenticateApiKey::ability('analytics:read'))->name('analytics.summary');
    });
});
