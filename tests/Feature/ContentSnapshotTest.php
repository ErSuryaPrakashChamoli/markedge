<?php

use App\Enums\PublishStatus;
use App\Models\Page;
use App\Models\User;
use Database\Seeders\ContentSnapshotSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->snapshotDirectory = storage_path('framework/testing/content-snapshot');
    File::deleteDirectory($this->snapshotDirectory);
    config(['markedge.content_snapshot_path' => $this->snapshotDirectory]);
    Storage::fake('public');
});

afterEach(function () {
    File::deleteDirectory($this->snapshotDirectory);
});

it('round-trips published content and uploaded files through the snapshot', function () {
    $this->seed(DatabaseSeeder::class);
    $editor = User::factory()->create();

    Page::where('slug', Page::HOME_SLUG)->update([
        'title' => 'Edited in admin',
        'status' => PublishStatus::Published,
        'created_by' => $editor->id,
    ]);
    Storage::disk('public')->put('blocks/hero/slide.jpg', 'image-bytes');

    $this->artisan('markedge:export-content')->assertSuccessful();

    Page::query()->update(['title' => 'Changed after export']);
    Storage::disk('public')->delete('blocks/hero/slide.jpg');

    $this->seed(ContentSnapshotSeeder::class);

    $home = Page::where('slug', Page::HOME_SLUG)->first();

    expect($home->title)->toBe('Edited in admin')
        ->and($home->status)->toBe(PublishStatus::Published)
        ->and($home->created_by)->toBeNull()
        ->and(Page::count())->toBe(12)
        ->and(Storage::disk('public')->get('blocks/hero/slide.jpg'))->toBe('image-bytes');
});

it('seeds the default admin when no credentials are configured', function () {
    $this->seed(ContentSnapshotSeeder::class);

    $admin = User::where('email', config('markedge.admin.email'))->first();

    expect($admin)->not->toBeNull()
        ->and($admin->isSuperAdmin())->toBeTrue();
});
