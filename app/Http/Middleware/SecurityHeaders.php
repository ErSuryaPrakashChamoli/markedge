<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Application-level security headers (Phase 10 §24) and cache-control discipline (§10).
 *
 * - Every response: nosniff, referrer policy, frame protection, permissions policy, HSTS (opt-in).
 * - Public site: nonce-based Content-Security-Policy. Vite and Livewire pick the nonce up
 *   automatically; Alpine evaluates attribute expressions itself and needs 'unsafe-eval'.
 * - Admin, Livewire and preview surfaces: no CSP (Filament ships inline scripts) and never cacheable.
 */
class SecurityHeaders
{
    /** @var array<int, string> path prefixes that are private: no CSP, no shared caching */
    protected const array PRIVATE_PREFIXES = ['admin', 'livewire', 'preview', 'go', 'health', 'up'];

    public function handle(Request $request, Closure $next): Response
    {
        $private = $this->isPrivate($request);
        $nonce = null;

        if (! $private && config('markedge.security.csp', true)) {
            $nonce = Vite::useCspNonce();
        }

        $response = $next($request);
        $headers = $response->headers;

        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('X-Frame-Options', 'SAMEORIGIN');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=(), interest-cohort=()');

        if ($request->isSecure() && config('markedge.security.hsts')) {
            $headers->set('Strict-Transport-Security', 'max-age='.(int) config('markedge.security.hsts_max_age', 31536000).'; includeSubDomains');
        }

        if ($nonce !== null && str_contains((string) $headers->get('Content-Type'), 'text/html')) {
            $headers->set(config('markedge.security.csp_report_only') ? 'Content-Security-Policy-Report-Only' : 'Content-Security-Policy', $this->policy($nonce, $request));
        }

        // Private surfaces and authenticated responses are never stored by any cache. Public HTML keeps
        // the framework default (no-cache, private): it varies by first-party cookies (attribution,
        // campaign CTA), so shared caching is opted in per route at the CDN, never assumed here.
        if (($private || $request->user() !== null) && ! str_contains((string) $headers->get('Cache-Control'), 'no-store')) {
            $headers->set('Cache-Control', 'no-store, private');
        }

        return $response;
    }

    protected function policy(string $nonce, Request $request): string
    {
        $media = implode(' ', array_merge(["'self'", 'data:', 'blob:'], (array) config('markedge.security.media_origins', [])));
        $frames = implode(' ', (array) config('markedge.security.frame_origins', []));

        $directives = [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}' 'unsafe-eval'",
            "style-src 'self' 'unsafe-inline'",
            "img-src {$media}",
            "media-src {$media}",
            "font-src 'self' data:",
            "connect-src 'self'",
            "frame-src {$frames}",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'",
        ];

        if ($request->isSecure()) {
            $directives[] = 'upgrade-insecure-requests';
        }

        return implode('; ', $directives);
    }

    protected function isPrivate(Request $request): bool
    {
        $path = ltrim($request->path(), '/');

        return Str::startsWith($path, array_merge(self::PRIVATE_PREFIXES, array_map(fn (string $p): string => "{$p}/", self::PRIVATE_PREFIXES)));
    }
}
