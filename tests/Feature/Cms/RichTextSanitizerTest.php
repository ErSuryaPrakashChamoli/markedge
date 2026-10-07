<?php

use App\Cms\RichTextSanitizer;

function sanitizeRichText(?string $html): string
{
    return app(RichTextSanitizer::class)->sanitize($html);
}

it('removes pasted inline styles and classes but keeps the structure', function () {
    $html = sanitizeRichText('<p style="text-align: justify;" class="MsoNormal">Gather <strong>enquiries</strong>.</p><h2 style="text-align: justify;"><strong>Advantages</strong></h2><ul><li style="color: red"><p>Centralised data</p></li></ul>');

    expect($html)
        ->not->toContain('style=')
        ->not->toContain('class=')
        ->toContain('<p>Gather <strong>enquiries</strong>.</p>')
        ->toContain('<h2><strong>Advantages</strong></h2>')
        ->toContain('<li><p>Centralised data</p></li>');
});

it('removes scripts, event handlers and script links', function (string $dangerous) {
    $html = sanitizeRichText('<p>Safe text</p>'.$dangerous);

    expect($html)
        ->toContain('<p>Safe text</p>')
        ->not->toContain('<script')
        ->not->toContain('onerror')
        ->not->toContain('onclick')
        ->not->toContain('javascript:')
        ->not->toContain('<iframe');
})->with([
    'script tag' => '<script>alert(1)</script>',
    'event handler' => '<img src="/x.png" onerror="alert(1)">',
    'script link' => '<a href="javascript:alert(1)" onclick="alert(1)">Click</a>',
    'iframe' => '<iframe src="https://evil.example/"></iframe>',
]);

it('turns a level-one heading into a section heading because the page title is the only h1', function () {
    expect(sanitizeRichText('<h1>Overview</h1><p>Text</p>'))
        ->toContain('<h2>Overview</h2>')
        ->not->toContain('<h1');
});

it('drops empty headings and paragraphs left by the editor', function () {
    expect(sanitizeRichText('<p>Text</p><h2 style="text-align: justify;"></h2><p></p>'))
        ->toBe('<p>Text</p>');
});

it('keeps tables, images and links that open in a new tab safely', function () {
    $html = sanitizeRichText('<table><tbody><tr><th>Plan</th><td colspan="2">Cloud</td></tr></tbody></table><img src="/storage/blocks/a.png" alt="Pipeline"><a href="https://example.com" target="_blank">Docs</a>');

    expect($html)
        ->toContain('<th>Plan</th><td colspan="2">Cloud</td>')
        ->toContain('<img src="/storage/blocks/a.png" alt="Pipeline"')
        ->toContain('href="https://example.com"')
        ->toContain('target="_blank"')
        ->toContain('noopener');
});

it('returns nothing for empty editor content', function (?string $empty) {
    expect(sanitizeRichText($empty))->toBe('');
})->with([null, '', '<p></p>', '<p><br></p>']);
