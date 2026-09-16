<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('pages.home-placeholder'))->name('home');

Route::get('/styleguide', function () {
    abort_unless(config('markedge.styleguide_enabled'), 404);

    return view('pages.styleguide');
})->name('styleguide');
