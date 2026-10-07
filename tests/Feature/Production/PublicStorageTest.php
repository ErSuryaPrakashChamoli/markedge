<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

it('serves an upload from the public disk when the web server has no copy behind its storage link', function () {
    Storage::fake('public');
    Storage::disk('public')->putFileAs('blocks/hero', UploadedFile::fake()->image('slide.jpg'), 'slide.jpg');

    $response = $this->get('/storage/blocks/hero/slide.jpg');

    $response->assertOk()->assertHeader('Content-Type', 'image/jpeg');
});

it('returns 404 for a missing upload under /storage', function () {
    Storage::fake('public');

    $this->get('/storage/blocks/hero/missing.jpg')->assertNotFound();
});

it('does not expose private disk files through the public storage url', function () {
    Storage::fake('public');
    Storage::fake('local');
    Storage::disk('local')->put('exports/leads.csv', 'secret');

    $this->get('/storage/exports/leads.csv')->assertNotFound();
    $this->get('/private-storage/exports/leads.csv')->assertForbidden();
});

it('rejects an unsigned upload to the public storage url', function () {
    Storage::fake('public');

    $this->call('PUT', '/storage/blocks/hero/shell.php?upload=1', content: '<?php phpinfo();')->assertForbidden();

    Storage::disk('public')->assertMissing('blocks/hero/shell.php');
});

it('warns when public/storage is a stale copy instead of a link to storage/app/public', function () {
    $public = sys_get_temp_dir().'/markedge-public-'.uniqid();
    File::ensureDirectoryExists($public.'/storage');
    app()->usePublicPath($public);

    $this->artisan('markedge:env-check')
        ->expectsOutputToContain('public/storage does not resolve to storage/app/public')
        ->assertSuccessful()
        ->run();

    File::deleteDirectory($public);
});

it('does not warn when public/storage links to storage/app/public', function () {
    $public = sys_get_temp_dir().'/markedge-public-'.uniqid();
    File::ensureDirectoryExists($public);
    File::ensureDirectoryExists(storage_path('app/public'));
    symlink(storage_path('app/public'), $public.'/storage');
    app()->usePublicPath($public);

    $this->artisan('markedge:env-check')
        ->doesntExpectOutputToContain('public/storage does not resolve')
        ->assertSuccessful()
        ->run();

    File::deleteDirectory($public);
});
