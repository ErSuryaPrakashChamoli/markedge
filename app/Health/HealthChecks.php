<?php

namespace App\Health;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Dependency probes for the readiness endpoint. Each method throws on failure and returns
 * nothing on success; the controller turns that into ok/fail without exposing details.
 */
class HealthChecks
{
    public function database(): void
    {
        DB::select('select 1');
    }

    public function cache(): void
    {
        $key = 'health:'.Str::random(8);
        Cache::put($key, 'ok', 10);

        if (Cache::pull($key) !== 'ok') {
            throw new RuntimeException('cache roundtrip failed');
        }
    }

    public function queue(): void
    {
        Queue::size();
    }
}
