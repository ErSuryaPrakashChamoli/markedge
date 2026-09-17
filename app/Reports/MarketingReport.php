<?php

namespace App\Reports;

use App\Enums\ConversionEventType;
use App\Enums\LeadStatus;
use App\Models\ConversionEvent;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Traffic, funnel, path, trend and interest reporting on top of the conversion report (Phase 13).
 * Everything is GROUP BY aggregation; rates are shown only where the denominator is measured.
 */
class MarketingReport extends ConversionReport
{
    /**
     * @return array<string, int>
     */
    public function traffic(): array
    {
        $views = $this->views();

        return [
            'page_views' => (clone $views)->count(),
            'sessions' => (clone $views)->whereNotNull('session_id')->distinct('session_id')->count('session_id'),
            'visitors' => (clone $views)->whereNotNull('visitor_id')->distinct('visitor_id')->count('visitor_id'),
            'landing_sessions' => (clone $views)->where(fn (Builder $q) => $this->metaFlag($q, 'landing'))->count(),
        ];
    }

    /**
     * Sessions → engaged (2+ views) → sessions with a CTA click → leads → qualified → converted,
     * with rates only when a denominator exists.
     *
     * @return array<string, array{count: int, rate: ?float}>
     */
    public function funnel(): array
    {
        $sessions = $this->traffic()['sessions'];
        $engaged = $this->views()->whereNotNull('session_id')->select('session_id')->groupBy('session_id')->havingRaw('COUNT(*) >= 2')->get()->count();
        $ctaSessions = $this->events()->where('type', ConversionEventType::CtaClicked->value)->whereNotNull('session_id')->distinct('session_id')->count('session_id');
        $leads = $this->leads()->count();
        $qualified = $this->leads()->whereIn('status', LeadStatus::values(LeadStatus::qualifiedOrBeyond()))->count();
        $converted = $this->leads()->where('status', LeadStatus::Converted->value)->count();
        $rate = fn (int $n, int $d): ?float => $d > 0 ? round(100 * $n / $d, 1) : null;

        return [
            'sessions' => ['count' => $sessions, 'rate' => null],
            'engaged_sessions' => ['count' => $engaged, 'rate' => $rate($engaged, $sessions)],
            'cta_sessions' => ['count' => $ctaSessions, 'rate' => $rate($ctaSessions, $sessions)],
            'leads' => ['count' => $leads, 'rate' => $rate($leads, $sessions)],
            'qualified' => ['count' => $qualified, 'rate' => $rate($qualified, $leads)],
            'converted' => ['count' => $converted, 'rate' => $rate($converted, $leads)],
        ];
    }

    /**
     * @return Collection<int, array{label: string, count: int}>
     */
    public function topPages(int $limit = 15): Collection
    {
        return $this->grouped($this->views(), 'path', $limit);
    }

    /**
     * @return Collection<int, array{label: string, count: int}>
     */
    public function landingPages(int $limit = 15): Collection
    {
        return $this->grouped($this->views()->where(fn (Builder $q) => $this->metaFlag($q, 'landing')), 'path', $limit);
    }

    /**
     * Sessions by the source/medium recorded on their landing view.
     *
     * @return Collection<int, array{label: string, count: int}>
     */
    public function sessionsBySource(int $limit = 15): Collection
    {
        return $this->views()->where(fn (Builder $q) => $this->metaFlag($q, 'landing'))
            ->selectRaw("COALESCE(source, '') as source, COALESCE(medium, '') as medium, COUNT(*) as aggregate")
            ->groupBy('source', 'medium')->orderByDesc('aggregate')->orderBy('source')->limit($limit)->get()
            ->map(fn ($row) => ['label' => ($row->source === '' ? 'Direct / Unknown' : $row->source).($row->medium === '' ? '' : ' / '.$row->medium), 'count' => (int) $row->aggregate]);
    }

    /**
     * Views and leads per product or service (interest).
     *
     * @return Collection<int, array{label: string, views: int, leads: int}>
     */
    public function interest(string $entityType, string $table, string $leadColumn, string $nameColumn = 'name', int $limit = 15): Collection
    {
        $views = $this->views()->where('entity_type', $entityType)->selectRaw('entity_id, COUNT(*) as aggregate')->groupBy('entity_id')->pluck('aggregate', 'entity_id');
        $leads = $this->leads()->whereNotNull($leadColumn)->selectRaw("{$leadColumn} as id, COUNT(*) as aggregate")->groupBy($leadColumn)->pluck('aggregate', 'id');
        $ids = $views->keys()->merge($leads->keys())->unique()->take(200);
        $names = $ids->isEmpty() ? collect() : DB::table($table)->whereIn('id', $ids)->pluck($nameColumn, 'id');

        return $ids->map(fn ($id) => ['label' => (string) ($names[$id] ?? '(deleted)'), 'views' => (int) ($views[$id] ?? 0), 'leads' => (int) ($leads[$id] ?? 0)])
            ->sortByDesc(fn (array $row) => [$row['leads'], $row['views']])->take($limit)->values();
    }

    /**
     * Most common event sequences before a lead (bounded to the latest 500 leads with a visitor id).
     *
     * @return Collection<int, array{label: string, count: int}>
     */
    public function conversionPaths(int $limit = 10): Collection
    {
        $leads = $this->leads()->whereNotNull('visitor_id')->latest('id')->limit(500)->get(['id', 'visitor_id', 'created_at']);

        if ($leads->isEmpty()) {
            return collect();
        }

        $events = ConversionEvent::query()->whereIn('visitor_id', $leads->pluck('visitor_id')->unique())
            ->whereIn('type', [ConversionEventType::PageViewed->value, ConversionEventType::SearchPerformed->value, ConversionEventType::CtaClicked->value, ConversionEventType::LeadCreated->value])
            ->orderBy('created_at')->get(['visitor_id', 'type', 'created_at', 'lead_id'])->groupBy('visitor_id');

        $paths = [];

        foreach ($leads as $lead) {
            $sequence = [];
            $lastToken = null;

            foreach ($events[$lead->visitor_id] ?? [] as $event) {
                if ($event->lead_id === $lead->id) {
                    $sequence[] = 'lead';

                    break;
                }

                if ($event->created_at > $lead->created_at) {
                    break;
                }

                $token = match ($event->type) {
                    ConversionEventType::PageViewed => 'view',
                    ConversionEventType::SearchPerformed => 'search',
                    ConversionEventType::CtaClicked => 'cta',
                    default => 'lead',
                };

                if ($token !== $lastToken) {
                    $sequence[] = $token;
                    $lastToken = $token;
                }
            }

            $label = $sequence === [] ? 'lead (no prior events)' : implode(' → ', array_slice($sequence, 0, 8));
            $paths[$label] = ($paths[$label] ?? 0) + 1;
        }

        arsort($paths);

        return collect(array_slice($paths, 0, $limit, true))->map(fn (int $count, string $label) => ['label' => $label, 'count' => $count])->values();
    }

    /**
     * Time series in the application timezone: daily, weekly, monthly or quarterly buckets.
     *
     * @return Collection<int, array{period: string, page_views: int, sessions: int, leads: int}>
     */
    public function trend(string $granularity = 'daily'): Collection
    {
        $bucket = $this->bucketExpression($granularity);

        $views = $this->views()->selectRaw("{$bucket} as period, COUNT(*) as views, COUNT(DISTINCT session_id) as sessions")->groupBy('period')->orderBy('period')->get()->keyBy('period');
        $leads = $this->leads()->selectRaw(str_replace('created_at', 'leads.created_at', $bucket).' as period, COUNT(*) as aggregate')->groupBy('period')->orderBy('period')->get()->keyBy('period');

        return $views->keys()->merge($leads->keys())->unique()->sort()->values()
            ->map(fn (string $period) => ['period' => $period, 'page_views' => (int) ($views[$period]->views ?? 0), 'sessions' => (int) ($views[$period]->sessions ?? 0), 'leads' => (int) ($leads[$period]->aggregate ?? 0)]);
    }

    /**
     * Content performance: views, searches landing on the page, CTA clicks from the page and leads
     * whose conversion page or first landing page it was.
     *
     * @return Collection<int, array{label: string, views: int, cta_clicks: int, leads: int}>
     */
    public function contentPerformance(int $limit = 15): Collection
    {
        $views = $this->views()->selectRaw('path, COUNT(*) as aggregate')->groupBy('path')->orderByDesc('aggregate')->limit(200)->pluck('aggregate', 'path');
        $ctas = $this->events()->where('type', ConversionEventType::CtaClicked->value)->whereNotNull('path')->selectRaw('path, COUNT(*) as aggregate')->groupBy('path')->pluck('aggregate', 'path');
        $leads = $this->leads()->whereNotNull('submitted_from_url')->selectRaw('submitted_from_url as path, COUNT(*) as aggregate')->groupBy('submitted_from_url')->pluck('aggregate', 'path');
        $influenced = $this->leads()->whereNotNull('first_landing_page')->selectRaw('first_landing_page as path, COUNT(*) as aggregate')->groupBy('first_landing_page')->pluck('aggregate', 'path');

        return $views->keys()->merge($ctas->keys())->merge($leads->keys())->unique()
            ->map(fn ($path) => ['label' => (string) $path, 'views' => (int) ($views[$path] ?? 0), 'cta_clicks' => (int) ($ctas[$path] ?? 0), 'leads' => (int) ($leads[$path] ?? 0), 'influenced' => (int) ($influenced[$path] ?? 0)])
            ->sortByDesc(fn (array $row) => [$row['leads'], $row['cta_clicks'], $row['views']])->take($limit)->values();
    }

    /**
     * @return Builder<ConversionEvent>
     */
    protected function views(): Builder
    {
        return $this->events()->where('type', ConversionEventType::PageViewed->value);
    }

    /**
     * @param  Builder<ConversionEvent>  $query
     * @return Collection<int, array{label: string, count: int}>
     */
    protected function grouped(Builder $query, string $column, int $limit): Collection
    {
        return $query->selectRaw("COALESCE({$column}, '') as label, COUNT(*) as aggregate")->groupBy('label')->orderByDesc('aggregate')->orderBy('label')->limit($limit)->get()
            ->map(fn ($row) => ['label' => $row->label === '' ? '(unknown)' : $row->label, 'count' => (int) $row->aggregate]);
    }

    /**
     * @param  Builder<ConversionEvent>  $query
     */
    protected function metaFlag(Builder $query, string $flag): Builder
    {
        $expression = $this->driver() === 'mysql' ? "JSON_EXTRACT(meta, '$.{$flag}') = true" : "json_extract(meta, '$.{$flag}') = 1";

        return $query->whereRaw($expression);
    }

    protected function bucketExpression(string $granularity): string
    {
        $offset = now(config('app.timezone'))->getOffset();
        $mysql = $this->driver() === 'mysql';
        $local = $mysql ? "DATE_ADD(created_at, INTERVAL {$offset} SECOND)" : "datetime(created_at, '+{$offset} seconds')";

        return match ($granularity) {
            'weekly' => $mysql ? "DATE_FORMAT({$local}, '%x-W%v')" : "strftime('%Y-W%W', {$local})",
            'monthly' => $mysql ? "DATE_FORMAT({$local}, '%Y-%m')" : "strftime('%Y-%m', {$local})",
            'quarterly' => $mysql ? "CONCAT(YEAR({$local}), '-Q', QUARTER({$local}))" : "strftime('%Y', {$local}) || '-Q' || ((CAST(strftime('%m', {$local}) AS INTEGER) + 2) / 3)",
            default => $mysql ? "DATE({$local})" : "date({$local})",
        };
    }

    protected function driver(): string
    {
        return ConversionEvent::query()->getConnection()->getDriverName();
    }
}
