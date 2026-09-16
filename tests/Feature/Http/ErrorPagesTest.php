<?php

it('renders the designed 404 page within the site layout', function () {
    $this->get('/this-page-does-not-exist')
        ->assertNotFound()
        ->assertSee('Page not found')
        ->assertSee('Skip to content');
});

it('marks the placeholder home page as indexable only in production', function () {
    $this->get('/')->assertSee('<meta name="robots" content="noindex, nofollow">', false);
});
