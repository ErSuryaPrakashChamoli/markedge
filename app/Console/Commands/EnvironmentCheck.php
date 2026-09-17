<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Throwable;

/**
 * Production configuration validation (Phase 10 §43). Prints pass/warn/fail per check and never
 * prints a secret. Exit code 1 when any check fails so deployments can gate on it.
 */
class EnvironmentCheck extends Command
{
    protected $signature = 'markedge:env-check';

    protected $description = 'Validate that the environment configuration is safe for the current APP_ENV (no secrets are printed)';

    public function handle(): int
    {
        $production = app()->isProduction();
        $rows = [];
        $failed = false;

        $check = function (string $name, bool $ok, string $detail, bool $fatal = true) use (&$rows, &$failed): void {
            $rows[] = [$name, $ok ? 'PASS' : ($fatal ? 'FAIL' : 'WARN'), $ok ? '' : $detail];
            $failed = $failed || (! $ok && $fatal);
        };

        $check('APP_KEY', filled(config('app.key')), 'Application key is missing.');
        $check('APP_DEBUG', ! ($production && config('app.debug')), 'Debug mode must be off in production.');
        $check('APP_URL', filled(config('app.url')) && (! $production || str_starts_with((string) config('app.url'), 'https://')), 'Production APP_URL must be an https URL.');
        $check('Database', $this->probe(fn () => DB::select('select 1')), 'Database connection failed.');
        $check('Cache store', ! ($production && in_array(config('cache.default'), ['array', 'null'], true)), 'Production must use redis, database or file cache.');
        $check('Queue', ! ($production && config('queue.default') === 'sync'), 'Production must not run the sync queue driver.');
        $check('Session cookie', ! $production || (bool) config('session.secure'), 'SESSION_SECURE_COOKIE=true is required in production.', fatal: $production);
        $check('Session driver', ! ($production && config('session.driver') === 'file'), 'Use redis or database sessions in production.', fatal: false);
        $check('Mail', ! ($production && in_array(config('mail.default'), ['log', 'array'], true)), 'Production mail is configured to log/array; lead notifications will not be delivered.', fatal: false);
        $check('Media disk', filled(config('media-library.disk_name')) && array_key_exists(config('media-library.disk_name'), config('filesystems.disks', [])), 'MEDIA_DISK must reference a configured filesystem disk.');
        $check('Indexability flag', env('MARKEDGE_INDEXABLE') !== null || ! $production, 'MARKEDGE_INDEXABLE must be set explicitly in production (true only on the live domain).', fatal: $production);
        $check('Trusted proxies', ! $production || filled(config('markedge.security.trusted_proxies')), 'Set TRUSTED_PROXIES when behind a CDN or load balancer so HTTPS and client IPs are detected.', fatal: false);
        $check('HSTS', ! $production || (bool) config('markedge.security.hsts'), 'Enable MARKEDGE_HSTS=true on HTTPS-only production.', fatal: false);
        $check('OPcache', ! $production || (function_exists('opcache_get_status') && (bool) ini_get('opcache.enable')), 'OPcache should be enabled in production.', fatal: false);
        $check('Timezone', filled(config('app.timezone')), 'APP_TIMEZONE must be set.');
        $check('Redis (if configured)', ! $this->usesRedis() || $this->probe(fn () => Redis::connection()->ping()), 'Redis is configured for cache/queue/session but does not answer.');

        $this->table(['Check', 'Result', 'Detail'], $rows);
        $this->line($failed ? 'Environment check FAILED.' : 'Environment check passed.');

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    protected function probe(\Closure $probe): bool
    {
        try {
            $probe();

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    protected function usesRedis(): bool
    {
        return config('cache.default') === 'redis' || config('queue.default') === 'redis' || config('session.driver') === 'redis';
    }
}
