<?php

namespace App\Http\Middleware;

use App\Analytics\Analytics;
use App\Attribution\AttributionCookie;
use App\Attribution\Normaliser;
use App\Attribution\TouchDetector;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps the attribution cookie current on public page views. No database work happens here.
 */
class CaptureAttribution
{
    /** @var array<int, string> */
    protected const array SKIP_PREFIXES = ['admin', 'livewire', 'preview', 'go', 'up', 'health', 'sitemap.xml', 'sitemap', 'robots.txt', 'build', 'storage', 'filament'];

    public function __construct(
        private readonly AttributionCookie $cookie,
        private readonly TouchDetector $detector,
        private readonly Analytics $analytics,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('GET') || $this->skips($request)) {
            return $next($request);
        }

        $attribution = $this->cookie->read($request);
        $landing = Normaliser::path('/'.ltrim($request->path(), '/')) ?? '/';
        $touch = $this->detector->detect($request, $landing);
        $before = $attribution->toArray();

        $attribution->recordVisit($touch, $landing);
        $newSession = $attribution->touchSession();

        $response = $next($request);

        $html = str_contains((string) $response->headers->get('Content-Type'), 'text/html');

        // One bounded insert per public HTML page (never a queue round-trip for a page view); Analytics
        // swallows storage failures so measurement can never break the page.
        if ($html && $response->getStatusCode() === 200) {
            $entity = $request->attributes->get('markedge.entity');
            $this->analytics->pageView($request, $attribution, $newSession, $entity instanceof Model ? $entity : null);
        }

        if ($html && $this->cookie->allowedFor($request) && ($before !== $attribution->toArray() || ! $request->cookies->has($this->cookie->name()))) {
            $response->headers->setCookie($this->cookie->make($attribution));
        }

        return $response;
    }

    protected function skips(Request $request): bool
    {
        $path = ltrim($request->path(), '/');

        foreach (self::SKIP_PREFIXES as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix.'/')) {
                return true;
            }
        }

        return $request->expectsJson() || $request->ajax();
    }
}
