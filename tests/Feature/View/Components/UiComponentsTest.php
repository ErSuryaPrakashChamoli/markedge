<?php

it('renders a button as a link when an href is given', function () {
    $html = (string) $this->blade('<x-ui.button href="/contact" variant="outline" size="lg">Talk to us</x-ui.button>');

    expect($html)->toContain('<a href="/contact"')
        ->toContain('Talk to us')
        ->toContain('border-line-strong')
        ->toContain('h-12');
});

it('renders a button element by default', function () {
    $html = (string) $this->blade('<x-ui.button type="submit">Send</x-ui.button>');

    expect($html)->toContain('<button type="submit"')->toContain('bg-brand');
});

it('scopes a dark section with the theme attribute', function () {
    $html = (string) $this->blade('<x-ui.section theme="dark" pattern="grid" id="hero">Content</x-ui.section>');

    expect($html)->toContain('data-theme="dark"')
        ->toContain('bg-grid-pattern')
        ->toContain('id="hero"')
        ->toContain('max-w-7xl');
});

it('renders a placeholder when no media is provided', function () {
    $html = (string) $this->blade('<x-ui.picture placeholder-label="Hero image" />');

    expect($html)->toContain('role="img"')->toContain('Hero image')->not->toContain('<img');
});

it('renders breadcrumbs with the current page marked', function () {
    $html = (string) $this->blade('<x-layout.breadcrumbs :items="[[\'label\' => \'Services\', \'url\' => \'/services\'], [\'label\' => \'SEO\']]" />');

    expect($html)->toContain('aria-label="Breadcrumb"')
        ->toContain('href="/services"')
        ->toContain('aria-current="page"')
        ->toContain('SEO');
});

it('renders a field with its error message linked to the control', function () {
    $html = (string) $this->blade('<x-forms.field id="email" label="Email" required error="Required."><x-forms.input id="email" invalid /></x-forms.field>');

    expect($html)->toContain('id="email-error"')
        ->toContain('aria-invalid="true"')
        ->toContain('aria-describedby="email-error"');
});
