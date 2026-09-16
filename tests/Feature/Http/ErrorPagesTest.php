<?php

it('renders the designed 404 page within the site layout', function () {
    $this->get('/this-page-does-not-exist')
        ->assertNotFound()
        ->assertSee('Page not found')
        ->assertSee('Skip to content');
});

it('emits noindex on every page when the environment is not indexable', function () {
    config(['markedge.seo.indexable' => false]);

    $this->get('/')->assertSee('<meta name="robots" content="noindex, nofollow">', false);
});
