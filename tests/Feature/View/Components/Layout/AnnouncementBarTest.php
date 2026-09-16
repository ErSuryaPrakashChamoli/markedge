<?php

use App\Models\Announcement;

it('shows an active announcement inside its date range', function () {
    Announcement::factory()->active()->create(['message' => 'We are hiring engineers.']);

    $this->get('/')->assertSee('We are hiring engineers.');
});

it('hides inactive, expired and upcoming announcements', function () {
    Announcement::factory()->create(['message' => 'Inactive notice']);
    Announcement::factory()->expired()->create(['message' => 'Expired notice']);
    Announcement::factory()->upcoming()->create(['message' => 'Upcoming notice']);

    $this->get('/')
        ->assertDontSee('Inactive notice')
        ->assertDontSee('Expired notice')
        ->assertDontSee('Upcoming notice');
});
