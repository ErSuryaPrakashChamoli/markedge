<?php

use App\Enums\SocialPlatform;

it('detects the platform from a pasted post link', function (string $url, ?SocialPlatform $platform) {
    expect(SocialPlatform::fromUrl($url))->toBe($platform);
})->with([
    ['https://www.instagram.com/reel/C1a2B3c4D5e/', SocialPlatform::Instagram],
    ['https://www.linkedin.com/posts/markedge_launch-activity-7123456789012345678-AbCd', SocialPlatform::LinkedIn],
    ['https://youtu.be/dQw4w9WgXcQ', SocialPlatform::YouTube],
    ['https://www.facebook.com/reel/123456789', SocialPlatform::Facebook],
    ['https://www.tiktok.com/@markedge/video/7234567890123456789', SocialPlatform::TikTok],
    ['https://x.com/markedge/status/1234567890', SocialPlatform::X],
    ['https://example.com/video', SocialPlatform::Other],
    ['not a url', null],
]);

it('builds the platform player url from a public post link', function (SocialPlatform $platform, string $url, ?string $embed) {
    expect($platform->embedUrl($url))->toBe($embed);
})->with([
    'instagram reel' => [SocialPlatform::Instagram, 'https://www.instagram.com/reel/C1a2B3c4D5e/?igsh=abc', 'https://www.instagram.com/reel/C1a2B3c4D5e/embed/'],
    'instagram reels path' => [SocialPlatform::Instagram, 'https://www.instagram.com/reels/C1a2B3c4D5e/', 'https://www.instagram.com/reel/C1a2B3c4D5e/embed/'],
    'instagram post' => [SocialPlatform::Instagram, 'https://www.instagram.com/markedge/p/C1a2B3c4D5e/', 'https://www.instagram.com/p/C1a2B3c4D5e/embed/'],
    'linkedin post' => [SocialPlatform::LinkedIn, 'https://www.linkedin.com/posts/markedge_launch-activity-7123456789012345678-AbCd/', 'https://www.linkedin.com/embed/feed/update/urn:li:activity:7123456789012345678'],
    'linkedin feed update' => [SocialPlatform::LinkedIn, 'https://www.linkedin.com/feed/update/urn:li:ugcPost:7123456789012345678/', 'https://www.linkedin.com/embed/feed/update/urn:li:ugcPost:7123456789012345678'],
    'youtube short' => [SocialPlatform::YouTube, 'https://www.youtube.com/shorts/dQw4w9WgXcQ', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ'],
    'youtube watch' => [SocialPlatform::YouTube, 'https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=10', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ'],
    'facebook reel' => [SocialPlatform::Facebook, 'https://www.facebook.com/reel/123456789', 'https://www.facebook.com/plugins/video.php?show_text=false&href=https%3A%2F%2Fwww.facebook.com%2Freel%2F123456789'],
    'tiktok' => [SocialPlatform::TikTok, 'https://www.tiktok.com/@markedge/video/7234567890123456789', 'https://www.tiktok.com/embed/v2/7234567890123456789'],
    'x links out' => [SocialPlatform::X, 'https://x.com/markedge/status/1234567890', null],
    'unrecognised instagram link' => [SocialPlatform::Instagram, 'https://www.instagram.com/markedge/', null],
]);
