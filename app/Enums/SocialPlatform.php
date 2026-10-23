<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Platforms a social video can be posted on. Each knows how to turn a public post URL into the
 * platform's own iframe player; platforms without one (or unrecognised URLs) link out instead.
 */
enum SocialPlatform: string implements HasLabel
{
    case Instagram = 'instagram';
    case LinkedIn = 'linkedin';
    case YouTube = 'youtube';
    case Facebook = 'facebook';
    case TikTok = 'tiktok';
    case X = 'x';
    case Vimeo = 'vimeo';
    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::Instagram => 'Instagram',
            self::LinkedIn => 'LinkedIn',
            self::YouTube => 'YouTube',
            self::Facebook => 'Facebook',
            self::TikTok => 'TikTok',
            self::X => 'X (Twitter)',
            self::Vimeo => 'Vimeo',
            self::Other => 'Other',
        };
    }

    /**
     * Guesses the platform from a pasted post URL.
     */
    public static function fromUrl(?string $url): ?self
    {
        $host = strtolower((string) parse_url((string) $url, PHP_URL_HOST));

        if ($host === '') {
            return null;
        }

        return match (true) {
            str_ends_with($host, 'instagram.com') => self::Instagram,
            str_ends_with($host, 'linkedin.com') => self::LinkedIn,
            str_ends_with($host, 'youtube.com'), $host === 'youtu.be' => self::YouTube,
            str_ends_with($host, 'facebook.com'), $host === 'fb.watch' => self::Facebook,
            str_ends_with($host, 'tiktok.com') => self::TikTok,
            str_ends_with($host, 'x.com'), str_ends_with($host, 'twitter.com') => self::X,
            str_ends_with($host, 'vimeo.com') => self::Vimeo,
            default => self::Other,
        };
    }

    /**
     * Platforms whose videos are usually vertical (reels, shorts).
     */
    public function prefersVertical(): bool
    {
        return in_array($this, [self::Instagram, self::TikTok], true);
    }

    /**
     * The platform's embeddable player URL for a public post, or null to link out to the post.
     */
    public function embedUrl(string $url): ?string
    {
        return match ($this) {
            self::Instagram => preg_match('#instagram\.com/(?:[A-Za-z0-9_.]+/)?(p|reels?|tv)/([A-Za-z0-9_-]+)#', $url, $m)
                ? 'https://www.instagram.com/'.($m[1] === 'reels' ? 'reel' : $m[1]).'/'.$m[2].'/embed/'
                : null,
            self::LinkedIn => preg_match('#urn(?::|%3A)li(?::|%3A)(activity|share|ugcPost)(?::|%3A)(\d+)#i', $url, $m)
                || preg_match('#linkedin\.com/posts/[^?\#]*-(activity|share|ugcPost)-(\d+)#i', $url, $m)
                ? 'https://www.linkedin.com/embed/feed/update/urn:li:'.$m[1].':'.$m[2]
                : null,
            self::YouTube => preg_match('#(?:youtu\.be/|[?&]v=|/embed/|/shorts/|/live/)([A-Za-z0-9_-]{6,})#', $url, $m)
                ? 'https://www.youtube-nocookie.com/embed/'.$m[1]
                : null,
            self::Facebook => preg_match('#^https://(www\.|m\.|web\.)?(facebook\.com/.+/videos/|facebook\.com/(reel|watch)|fb\.watch/)#', $url)
                ? 'https://www.facebook.com/plugins/video.php?show_text=false&href='.rawurlencode($url)
                : null,
            self::TikTok => preg_match('#tiktok\.com/@[^/]+/video/(\d+)#', $url, $m)
                ? 'https://www.tiktok.com/embed/v2/'.$m[1]
                : null,
            self::Vimeo => preg_match('#vimeo\.com/(?:video/)?(\d+)#', $url, $m)
                ? 'https://player.vimeo.com/video/'.$m[1]
                : null,
            self::X, self::Other => null,
        };
    }
}
