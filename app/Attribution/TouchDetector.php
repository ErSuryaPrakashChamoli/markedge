<?php

namespace App\Attribution;

use Illuminate\Http\Request;

/**
 * Builds a Touch from a request when it carries acquisition signals (UTM parameters, an ad click
 * id or an external referrer). Direct revisits produce no touch.
 */
class TouchDetector
{
    /** @var array<string, array{0: string, 1: string}> host fragment => [source, medium] */
    protected const array KNOWN_REFERRERS = [
        'google.' => ['google', 'organic'],
        'bing.com' => ['bing', 'organic'],
        'duckduckgo.com' => ['duckduckgo', 'organic'],
        'yahoo.' => ['yahoo', 'organic'],
        'linkedin.com' => ['linkedin', 'social'],
        'lnkd.in' => ['linkedin', 'social'],
        'facebook.com' => ['facebook', 'social'],
        'instagram.com' => ['instagram', 'social'],
        'twitter.com' => ['x', 'social'],
        'x.com' => ['x', 'social'],
        't.co' => ['x', 'social'],
        'youtube.com' => ['youtube', 'social'],
        'whatsapp.com' => ['whatsapp', 'messaging'],
    ];

    public function detect(Request $request, string $landingPage): ?Touch
    {
        $utm = [];

        foreach (['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'] as $key) {
            $raw = $request->query($key);
            $utm[$key] = is_string($raw) ? Normaliser::bound($raw, lower: in_array($key, ['utm_source', 'utm_medium', 'utm_campaign'], true)) : null;
        }

        $referrer = Normaliser::referrerHost($request->headers->get('referer'));
        $external = $referrer !== null && $referrer !== mb_strtolower((string) parse_url(config('app.url'), PHP_URL_HOST)) && $referrer !== mb_strtolower($request->getHost());

        if ($utm['utm_source'] !== null || $utm['utm_campaign'] !== null) {
            return new Touch(
                source: $utm['utm_source'] ?? 'unknown',
                medium: $utm['utm_medium'],
                campaign: $utm['utm_campaign'],
                term: $utm['utm_term'],
                content: $utm['utm_content'],
                referrer: $external ? $referrer : null,
                landingPage: $landingPage,
                at: now()->toIso8601String(),
            );
        }

        if (is_string($request->query('gclid')) && $request->query('gclid') !== '') {
            return new Touch('google', 'cpc', null, null, null, $external ? $referrer : null, $landingPage, now()->toIso8601String());
        }

        if (is_string($request->query('fbclid')) && $request->query('fbclid') !== '') {
            return new Touch('facebook', 'paid_social', null, null, null, $external ? $referrer : null, $landingPage, now()->toIso8601String());
        }

        if ($external) {
            [$source, $medium] = $this->classify($referrer);

            return new Touch($source, $medium, null, null, null, $referrer, $landingPage, now()->toIso8601String());
        }

        return null;
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function classify(string $host): array
    {
        foreach (self::KNOWN_REFERRERS as $fragment => $classification) {
            if (str_contains($host, $fragment)) {
                return $classification;
            }
        }

        return [$host, 'referral'];
    }
}
