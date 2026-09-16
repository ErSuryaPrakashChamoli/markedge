<?php

namespace App\Reports;

use App\Enums\ConversionEventType;
use App\Enums\LeadStatus;
use App\Models\ConversionEvent;
use App\Models\CtaClick;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Factual conversion counts for a date range, computed with GROUP BY aggregation only.
 * No rates are shown because the site has no reliable visit denominator (Phase 8 §37).
 */
class ConversionReport
{
    public function __construct(public readonly Carbon $from, public readonly Carbon $until) {}

    /**
     * Named ranges in the application timezone; `until` is exclusive.
     *
     * @return array<string, string>
     */
    public static function ranges(): array
    {
        return [
            'today' => 'Today',
            'yesterday' => 'Yesterday',
            'last_7' => 'Last 7 days',
            'last_30' => 'Last 30 days',
            'this_month' => 'This month',
            'previous_month' => 'Previous month',
            'custom' => 'Custom',
        ];
    }

    public static function forRange(string $range, ?string $from = null, ?string $until = null): self
    {
        $now = now();

        [$start, $end] = match ($range) {
            'today' => [$now->copy()->startOfDay(), $now->copy()->addDay()->startOfDay()],
            'yesterday' => [$now->copy()->subDay()->startOfDay(), $now->copy()->startOfDay()],
            'last_7' => [$now->copy()->subDays(6)->startOfDay(), $now->copy()->addDay()->startOfDay()],
            'this_month' => [$now->copy()->startOfMonth(), $now->copy()->addDay()->startOfDay()],
            'previous_month' => [$now->copy()->subMonthNoOverflow()->startOfMonth(), $now->copy()->startOfMonth()],
            'custom' => [
                self::parse($from) ?? $now->copy()->subDays(29)->startOfDay(),
                (self::parse($until) ?? $now->copy()->startOfDay())->addDay(),
            ],
            default => [$now->copy()->subDays(29)->startOfDay(), $now->copy()->addDay()->startOfDay()],
        };

        if ($end->lessThanOrEqualTo($start)) {
            $end = $start->copy()->addDay();
        }

        return new self($start, $end);
    }

    protected static function parse(?string $date): ?Carbon
    {
        try {
            return filled($date) ? Carbon::parse($date, config('app.timezone'))->startOfDay() : null;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return array<string, int>
     */
    public function totals(): array
    {
        $events = $this->events()->selectRaw('type, COUNT(*) as aggregate')->groupBy('type')->pluck('aggregate', 'type');

        return [
            'leads' => $this->leads()->count(),
            'form_submissions' => (int) ($events[ConversionEventType::FormSubmitted->value] ?? 0),
            'cta_clicks' => $this->clicks()->count(),
            'searches' => (int) ($events[ConversionEventType::SearchPerformed->value] ?? 0),
            'leads_after_search' => $this->leads()->whereExists(fn ($q) => $q->selectRaw('1')->from('conversion_events')
                ->whereColumn('conversion_events.visitor_id', 'leads.visitor_id')
                ->whereColumn('conversion_events.created_at', '<=', 'leads.created_at')
                ->where('conversion_events.type', ConversionEventType::SearchPerformed->value))->count(),
        ];
    }

    /**
     * @return Collection<int, array{label: string, count: int}>
     */
    public function leadsBy(string $column, string $fallback = 'Direct / Unknown', int $limit = 15): Collection
    {
        return $this->leads()
            ->selectRaw("COALESCE({$column}, '') as label, COUNT(*) as aggregate")
            ->groupBy('label')->orderByDesc('aggregate')->orderBy('label')->limit($limit)->get()
            ->map(fn ($row) => ['label' => $row->label === '' ? $fallback : $row->label, 'count' => (int) $row->aggregate]);
    }

    /**
     * @return Collection<int, array{label: string, count: int}>
     */
    public function leadsBySourceMedium(string $prefix, int $limit = 15): Collection
    {
        return $this->leads()
            ->selectRaw("COALESCE({$prefix}_source, '') as source, COALESCE({$prefix}_medium, '') as medium, COUNT(*) as aggregate")
            ->groupBy('source', 'medium')->orderByDesc('aggregate')->orderBy('source')->limit($limit)->get()
            ->map(fn ($row) => ['label' => ($row->source === '' ? 'Direct / Unknown' : $row->source).($row->medium === '' ? '' : ' / '.$row->medium), 'count' => (int) $row->aggregate]);
    }

    /**
     * Leads grouped by a related record's name (form, service, product, solution, campaign).
     *
     * @return Collection<int, array{label: string, count: int}>
     */
    public function leadsByRelation(string $table, string $foreignKey, string $nameColumn = 'name', int $limit = 15): Collection
    {
        return $this->leads()
            ->leftJoin($table, "{$table}.id", '=', "leads.{$foreignKey}")
            ->selectRaw("COALESCE({$table}.{$nameColumn}, '') as label, COUNT(*) as aggregate")
            ->whereNotNull("leads.{$foreignKey}")
            ->groupBy('label')->orderByDesc('aggregate')->orderBy('label')->limit($limit)->get()
            ->map(fn ($row) => ['label' => $row->label === '' ? '(deleted)' : $row->label, 'count' => (int) $row->aggregate]);
    }

    /**
     * @return Collection<int, array{label: string, count: int}>
     */
    public function ctaClicks(int $limit = 15): Collection
    {
        return $this->clicks()
            ->leftJoin('ctas', 'ctas.id', '=', 'cta_clicks.cta_id')
            ->selectRaw("COALESCE(ctas.name, '') as label, COUNT(*) as aggregate")
            ->groupBy('label')->orderByDesc('aggregate')->orderBy('label')->limit($limit)->get()
            ->map(fn ($row) => ['label' => $row->label === '' ? '(deleted CTA)' : $row->label, 'count' => (int) $row->aggregate]);
    }

    /**
     * @return Collection<int, array{label: string, count: int}>
     */
    public function ctaClicksByPage(int $limit = 15): Collection
    {
        return $this->clicks()
            ->selectRaw("COALESCE(path, '') as label, COUNT(*) as aggregate")
            ->groupBy('label')->orderByDesc('aggregate')->orderBy('label')->limit($limit)->get()
            ->map(fn ($row) => ['label' => $row->label === '' ? '(unknown page)' : $row->label, 'count' => (int) $row->aggregate]);
    }

    /**
     * Aggregated search terms only; never a per-visitor history.
     *
     * @return Collection<int, array{label: string, count: int}>
     */
    public function topSearches(int $limit = 15): Collection
    {
        $driver = ConversionEvent::query()->getConnection()->getDriverName();
        $expression = $driver === 'mysql' ? "JSON_UNQUOTE(JSON_EXTRACT(meta, '$.query'))" : "json_extract(meta, '$.query')";

        return $this->events()->where('type', ConversionEventType::SearchPerformed->value)
            ->selectRaw("{$expression} as label, COUNT(*) as aggregate")
            ->groupBy('label')->orderByDesc('aggregate')->orderBy('label')->limit($limit)->get()
            ->map(fn ($row) => ['label' => (string) $row->label, 'count' => (int) $row->aggregate]);
    }

    /**
     * @return Builder<Lead>
     */
    protected function leads(): Builder
    {
        return Lead::query()->where('leads.status', '!=', LeadStatus::Spam->value)
            ->where('leads.created_at', '>=', $this->from)->where('leads.created_at', '<', $this->until);
    }

    /**
     * @return Builder<ConversionEvent>
     */
    protected function events(): Builder
    {
        return ConversionEvent::query()->where('created_at', '>=', $this->from)->where('created_at', '<', $this->until);
    }

    /**
     * @return Builder<CtaClick>
     */
    protected function clicks(): Builder
    {
        return CtaClick::query()->where('cta_clicks.created_at', '>=', $this->from)->where('cta_clicks.created_at', '<', $this->until);
    }
}
