<?php

namespace App\Ops;

use App\Enums\AutomationRunStatus;
use App\Health\HealthChecks;
use App\Models\ApiKey;
use App\Models\AutomationRule;
use App\Models\AutomationRun;
use App\Models\ConversionEvent;
use App\Models\NotificationDelivery;
use App\Models\SearchEntry;
use App\Notifications\Channels\ChannelRegistry;
use App\Providers\AiServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Facts for the Operations page: what is configured, what ran, what failed. Every value is read
 * from the database or configuration; nothing is estimated and nothing is assumed healthy.
 */
class OperationsStatus
{
    public function __construct(private readonly HealthChecks $health, private readonly ChannelRegistry $channels) {}

    /**
     * @return array<string, array{ok: bool, detail: string}>
     */
    public function dependencies(): array
    {
        $checks = [];

        foreach (['database', 'cache', 'queue'] as $name) {
            try {
                $this->health->{$name}();
                $checks[$name] = ['ok' => true, 'detail' => 'ok ('.config(match ($name) {
                    'database' => 'database.default', 'cache' => 'cache.default', default => 'queue.default'
                }).')'];
            } catch (Throwable $exception) {
                $checks[$name] = ['ok' => false, 'detail' => class_basename($exception)];
            }
        }

        return $checks;
    }

    /**
     * @return array{driver: string, pending: int|null, failed: int|null, failed_24h: int|null, oldest_pending_minutes: int|null}
     */
    public function queue(): array
    {
        $driver = (string) config('queue.default');
        $pending = Schema::hasTable('jobs') ? (int) DB::table('jobs')->count() : null;
        $oldest = Schema::hasTable('jobs') ? DB::table('jobs')->min('available_at') : null;

        return [
            'driver' => $driver,
            'pending' => $driver === 'database' ? $pending : null,
            'failed' => Schema::hasTable('failed_jobs') ? (int) DB::table('failed_jobs')->count() : null,
            'failed_24h' => Schema::hasTable('failed_jobs') ? (int) DB::table('failed_jobs')->where('failed_at', '>=', now()->subDay())->count() : null,
            'oldest_pending_minutes' => $driver === 'database' && $oldest ? max(0, (int) ((now()->timestamp - (int) $oldest) / 60)) : null,
        ];
    }

    /**
     * @return array{rules: int, active: int, runs_24h: int, succeeded_24h: int, failed_24h: int, skipped_24h: int, last_run_at: string|null}
     */
    public function automation(): array
    {
        $since = now()->subDay();
        $byStatus = AutomationRun::query()->where('created_at', '>=', $since)->selectRaw('status, COUNT(*) as aggregate')->groupBy('status')->get()->mapWithKeys(fn ($row) => [$row->status->value => (int) $row->aggregate]);

        return [
            'rules' => AutomationRule::query()->count(),
            'active' => AutomationRule::query()->active()->count(),
            'runs_24h' => (int) $byStatus->sum(),
            'succeeded_24h' => (int) ($byStatus[AutomationRunStatus::Succeeded->value] ?? 0),
            'failed_24h' => (int) ($byStatus[AutomationRunStatus::Failed->value] ?? 0),
            'skipped_24h' => (int) ($byStatus[AutomationRunStatus::Skipped->value] ?? 0),
            'last_run_at' => AutomationRun::query()->max('finished_at'),
        ];
    }

    /**
     * @return array<string, array{configured: bool, status: string, sent_24h: int, failed_24h: int, skipped_24h: int}>
     */
    public function channels(): array
    {
        $rows = NotificationDelivery::query()->where('created_at', '>=', now()->subDay())->selectRaw('channel, status, COUNT(*) as aggregate')->groupBy('channel', 'status')->get();
        $out = [];

        foreach ($this->channels->all() as $name => $channel) {
            $out[$name] = [
                'configured' => $channel->isConfigured(),
                'status' => $channel->status(),
                'sent_24h' => (int) $rows->where('channel', $name)->where('status', 'sent')->sum('aggregate'),
                'failed_24h' => (int) $rows->where('channel', $name)->where('status', 'failed')->sum('aggregate'),
                'skipped_24h' => (int) $rows->where('channel', $name)->where('status', 'skipped')->sum('aggregate'),
            ];
        }

        return $out;
    }

    /**
     * @return array{keys: int, active: int, last_used_at: string|null, requests_24h: int, writes_24h: int, rate_limit_per_minute: int}
     */
    public function api(): array
    {
        $log = DB::table('activity_log')->where('log_name', 'api')->where('created_at', '>=', now()->subDay());

        return [
            'keys' => ApiKey::query()->count(),
            'active' => ApiKey::query()->where('is_active', true)->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))->count(),
            'last_used_at' => ApiKey::query()->max('last_used_at'),
            'requests_24h' => (int) (clone $log)->count(),
            'writes_24h' => (int) (clone $log)->where('description', 'like', 'API leads.store%')->count(),
            'rate_limit_per_minute' => (int) config('markedge.rate_limits.api', 60),
        ];
    }

    /**
     * @return array<string, int|string|null>
     */
    public function data(): array
    {
        return [
            'search_entries' => SearchEntry::query()->count(),
            'conversion_events' => ConversionEvent::query()->count(),
            'oldest_page_view' => ConversionEvent::query()->where('type', 'page_viewed')->min('created_at'),
            'pageview_retention_days' => (int) config('markedge.analytics.pageview_retention_days'),
            'event_retention_days' => (int) config('markedge.analytics.event_retention_days'),
            'activity_log_rows' => Schema::hasTable('activity_log') ? (int) DB::table('activity_log')->count() : null,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function environment(): array
    {
        return [
            'app_env' => (string) config('app.env'),
            'debug' => config('app.debug') ? 'ON (must be off in production)' : 'off',
            'indexable' => config('markedge.seo.indexable') ? 'yes' : 'no',
            'cache' => (string) config('cache.default'),
            'session' => (string) config('session.driver'),
            'queue' => (string) config('queue.default'),
            'mail' => (string) config('mail.default'),
            'ai' => AiServiceProvider::status(),
            'scheduler' => 'Runs only if the system cron calls artisan schedule:run (NOT VERIFIED from inside the app)',
        ];
    }
}
