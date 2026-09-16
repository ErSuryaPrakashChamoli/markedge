<?php

it('renders the styleguide when enabled', function () {
    config(['markedge.styleguide_enabled' => true]);

    $this->get('/styleguide')
        ->assertOk()
        ->assertSee('Markedge styleguide')
        ->assertSee('noindex, nofollow');
});

it('returns 404 for the styleguide when disabled', function () {
    config(['markedge.styleguide_enabled' => false]);

    $this->get('/styleguide')->assertNotFound();
});
