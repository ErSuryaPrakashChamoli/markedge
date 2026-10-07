<?php

namespace App\Cms;

use Illuminate\Support\Facades\Cache;
use Mews\Purifier\Facades\Purifier;

/**
 * Turns stored rich text into the HTML the public site prints (architecture §31: allow-list purifier).
 *
 * Only structure survives: headings, paragraphs, lists, links, tables and images. Inline
 * styles, classes, scripts and empty blocks are removed, so text pasted from another
 * document or written in the HTML editor always follows the design system. Results are
 * cached by content hash, so each distinct text is purified once.
 */
class RichTextSanitizer
{
    /** Bump when the `cms` purifier profile changes so cached output is rebuilt. */
    public const int VERSION = 1;

    public function sanitize(?string $html): string
    {
        if (blank(strip_tags((string) $html, '<img>'))) {
            return '';
        }

        return Cache::remember(
            'rich-text:v'.self::VERSION.':'.hash('xxh128', $html),
            now()->addDays(30),
            fn (): string => $this->purify($html),
        );
    }

    protected function purify(string $html): string
    {
        // The page title is the page's only h1, so a level-one heading in content becomes a section heading.
        $html = preg_replace('#<(/?)h1\b#i', '<$1h2', $html) ?? $html;

        return trim((string) Purifier::clean($html, 'cms'));
    }
}
