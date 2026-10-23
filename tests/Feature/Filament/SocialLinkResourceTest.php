<?php

use App\Filament\Resources\SocialLinks\Pages\ManageSocialLinks;
use App\Models\SocialLink;
use Filament\Actions\Testing\TestAction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('adds a platform that is not in the list with its own uploaded icon', function () {
    Storage::fake('public');
    $this->actingAs(adminUser());

    Livewire::test(ManageSocialLinks::class)
        ->callAction('create', data: [
            'platform' => 'other',
            'label' => 'TikTok',
            'url' => 'https://www.tiktok.com/@markedge',
            'icon' => UploadedFile::fake()->image('tiktok.png', 64, 64),
            'is_visible' => true,
            'sort_order' => 5,
        ])
        ->assertHasNoActionErrors();

    $link = SocialLink::query()->sole();

    expect($link->icon)->toStartWith('social-icons/');
    Storage::disk('public')->assertExists($link->icon);
    $this->get('/')
        ->assertSee('aria-label="TikTok"', false)
        ->assertSee('<img src="'.$link->iconUrl().'"', false);
});

it('requires a name for a platform that is not in the list', function () {
    $this->actingAs(adminUser());

    Livewire::test(ManageSocialLinks::class)
        ->callAction('create', data: ['platform' => 'other', 'label' => '', 'url' => 'https://example.test/markedge'])
        ->assertHasActionErrors(['label' => 'required']);

    expect(SocialLink::query()->count())->toBe(0);
});

it('updates the profile URL of an existing social link', function () {
    $link = SocialLink::factory()->create(['platform' => 'instagram', 'url' => 'https://www.instagram.com/']);
    $this->actingAs(adminUser());

    Livewire::test(ManageSocialLinks::class)
        ->callAction(TestAction::make('edit')->table($link), data: ['url' => 'https://www.instagram.com/markedge'])
        ->assertHasNoActionErrors();

    expect($link->fresh()->url)->toBe('https://www.instagram.com/markedge');
});
