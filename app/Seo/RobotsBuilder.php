<?php

namespace App\Seo;

/**
 * robots.txt from configuration only. CMS content never reaches this file.
 */
class RobotsBuilder
{
    /** @var array<int, string> */
    public const array PRIVATE_PATHS = ['/admin', '/preview', '/livewire', '/styleguide', '/go', '/up'];

    public function __construct(private readonly IndexabilityResolver $urls) {}

    public function build(): string
    {
        $lines = ['User-agent: *'];

        if (config('markedge.seo.indexable')) {
            foreach (self::PRIVATE_PATHS as $path) {
                $lines[] = "Disallow: {$path}";
            }

            $lines[] = 'Allow: /';
            $lines[] = '';
            $lines[] = 'Sitemap: '.$this->urls->absolute('/sitemap.xml');
        } else {
            $lines[] = 'Disallow: /';
        }

        return implode("\n", $lines)."\n";
    }
}
