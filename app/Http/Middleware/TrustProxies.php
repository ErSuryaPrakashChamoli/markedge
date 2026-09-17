<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

/**
 * Trusted proxies come from configuration (TRUSTED_PROXIES: "*" or a comma-separated list) so the
 * setting survives config caching and never lives in code.
 */
class TrustProxies extends Middleware
{
    protected function proxies(): array|string|null
    {
        $proxies = config('markedge.security.trusted_proxies');

        if (blank($proxies)) {
            return null;
        }

        return $proxies === '*' ? '*' : array_map('trim', explode(',', (string) $proxies));
    }

    protected function headers(): int
    {
        return Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_HOST | Request::HEADER_X_FORWARDED_PORT | Request::HEADER_X_FORWARDED_PROTO | Request::HEADER_X_FORWARDED_AWS_ELB;
    }
}
