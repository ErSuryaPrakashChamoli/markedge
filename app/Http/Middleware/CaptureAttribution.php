<?php

namespace App\Http\Middleware;

use App\Attribution\AttributionCookie;
use App\Attribution\Normaliser;
use App\Attribution\TouchDetector;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps the attribution cookie current on public page views. No database work happens here.
 */
class CaptureAttribution
{
    /** @var array<int, string> */
    protected const array SKIP_PREFIXES = ['admin', 'livewire', 'preview', 'go', 'up', 'sitemap.xml', 'sitemap', 'robots.txt', 'build', 'storage', 'filament'];

    public function __construct(
        private readonly AttributionCookie $cookie,
        private readonly TouchDetector $detector,
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

        $response = $next($request);

        $html = str_contains((string) $response->headers->get('Content-Type'), 'text/html');

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
