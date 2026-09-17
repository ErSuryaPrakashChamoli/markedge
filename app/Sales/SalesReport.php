<?php

namespace App\Sales;

use App\Enums\LeadPriority;
use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\LeadFollowUp;
use App\Models\User;
use App\Reports\ConversionReport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Sales dashboard aggregates (Phase 14). Every number is a count, sum or average of stored leads;
 * rates appear only when their denominator exists and money only when sales entered it.
 */
class SalesReport
{
    public function __construct(public readonly Carbon $from, public readonly Carbon $until) {}

    public static function forRange(string $range, ?string $from = null, ?string $until = null): static
    {
        $window = ConversionReport::forRange($range, $from, $until);

        return new static($window->from, $window->until);
    }

    /**
     * Open leads per stage, in pipeline order.
     *
     * @return array<string, array{label: string, count: int, oldest_days: int|null}>
     */
    public function pipeline(): array
    {
        $rows = Lead::query()->open()
            ->selectRaw('status, COUNT(*) as aggregate, MIN(stage_entered_at) as oldest')
            ->groupBy('status')->get()->keyBy(fn (Lead $row): string => $row->status->value);

        $pipeline = [];

        foreach (LeadStatus::pipeline() as $status) {
            $row = $rows->get($status->value);
            $pipeline[$status->value] = [
                'label' => $status->getLabel(),
                'count' => (int) ($row->aggregate ?? 0),
                'oldest_days' => isset($row->oldest) ? (int) Carbon::parse($row->oldest)->diffInDays(now()) : null,
            ];
        }

        return $pipeline;
    }

    /**
     * @return array{new: int, won: int, lost: int, unqualified: int, closed: int, win_rate: float|null}
     */
    public function outcomes(): array
    {
        $new = $this->newLeads()->count();
        $closed = Lead::query()->notSpam()->closed()
            ->whereNotNull('closed_at')->where('closed_at', '>=', $this->from)->where('closed_at', '<', $this->until)
            ->selectRaw('status, COUNT(*) as aggregate')->groupBy('status')->get()->mapWithKeys(fn (Lead $row) => [$row->status->value => (int) $row->aggregate]);

        $won = (int) ($closed[LeadStatus::Converted->value] ?? 0);
        $lost = (int) ($closed[LeadStatus::Lost->value] ?? 0);
        $unqualified = (int) ($closed[LeadStatus::Unqualified->value] ?? 0);

        return [
            'new' => $new,
            'won' => $won,
            'lost' => $lost,
            'unqualified' => $unqualified,
            'closed' => $won + $lost + $unqualified,
            'win_rate' => $won + $lost > 0 ? round($won / ($won + $lost) * 100, 1) : null,
        ];
    }

    /**
     * @return array{configured: bool, target_hours: int|null, breaching: int|null, contacted_in_range: int, within_target: int|null, average_hours: float|null}
     */
    public function firstContact(): array
    {
        $target = config('markedge.sales.sla.first_contact_hours');
        $target = $target === null ? null : (int) $target;

        $contacted = Lead::query()->notSpam()->whereNotNull('contacted_at')
            ->where('contacted_at', '>=', $this->from)->where('contacted_at', '<', $this->until);

        $count = (clone $contacted)->count();
        $average = $count > 0 ? (clone $contacted)->selectRaw($this->hoursBetween('created_at', 'contacted_at').' as hours')->get()->avg('hours') : null;

        $within = null;
        $breaching = null;

        if ($target !== null) {
            $within = $count > 0 ? (clone $contacted)->whereRaw($this->hoursBetween('created_at', 'contacted_at').' <= ?', [$target])->count() : 0;
            $breaching = Lead::query()->open()->whereNull('contacted_at')->where('created_at', '<', now()->subHours($target))->count();
        }

        return [
            'configured' => $target !== null,
            'target_hours' => $target,
            'breaching' => $breaching,
            'contacted_in_range' => $count,
            'within_target' => $within,
            'average_hours' => $average === null ? null : round((float) $average, 1),
        ];
    }

    /**
     * @return array{overdue: int, due_today: int, upcoming: int}
     */
    public function followUps(?int $userId = null): array
    {
        $base = LeadFollowUp::query()->open()->when($userId, fn (Builder $q) => $q->where('user_id', $userId));

        return [
            'overdue' => (clone $base)->where('due_at', '<', now())->count(),
            'due_today' => (clone $base)->whereBetween('due_at', [now(), now()->endOfDay()])->count(),
            'upcoming' => (clone $base)->where('due_at', '>', now()->endOfDay())->count(),
        ];
    }

    /**
     * Open leads per owner, the owner's overdue follow-ups and leads won in the range.
     *
     * @return Collection<int, array{label: string, open: int, overdue: int, won: int}>
     */
    public function owners(): Collection
    {
        $open = Lead::query()->open()->whereNotNull('assigned_to')->selectRaw('assigned_to, COUNT(*) as aggregate')->groupBy('assigned_to')->pluck('aggregate', 'assigned_to');
        $overdue = LeadFollowUp::query()->overdue()->whereNotNull('user_id')->selectRaw('user_id, COUNT(*) as aggregate')->groupBy('user_id')->pluck('aggregate', 'user_id');
        $won = Lead::query()->where('status', LeadStatus::Converted)->whereNotNull('assigned_to')
            ->whereNotNull('closed_at')->where('closed_at', '>=', $this->from)->where('closed_at', '<', $this->until)
            ->selectRaw('assigned_to, COUNT(*) as aggregate')->groupBy('assigned_to')->pluck('aggregate', 'assigned_to');

        $ids = $open->keys()->merge($overdue->keys())->merge($won->keys())->unique()->values();
        $names = $ids->isEmpty() ? collect() : User::query()->whereIn('id', $ids)->pluck('name', 'id');

        return $ids->map(fn ($id) => [
            'label' => (string) ($names[$id] ?? "User #{$id}"),
            'open' => (int) ($open[$id] ?? 0),
            'overdue' => (int) ($overdue[$id] ?? 0),
            'won' => (int) ($won[$id] ?? 0),
        ])->sortByDesc('open')->values();
    }

    /**
     * @return Collection<int, array{label: string, count: int}>
     */
    public function lostReasons(): Collection
    {
        $reasons = config('markedge.sales.lost_reasons', []);

        return Lead::query()->where('status', LeadStatus::Lost)
            ->whereNotNull('closed_at')->where('closed_at', '>=', $this->from)->where('closed_at', '<', $this->until)
            ->selectRaw('lost_reason, COUNT(*) as aggregate')->groupBy('lost_reason')->orderByDesc('aggregate')->get()
            ->map(fn ($row) => ['label' => (string) ($reasons[$row->lost_reason] ?? $row->lost_reason ?? 'Not given'), 'count' => (int) $row->aggregate]);
    }

    /**
     * @return Collection<int, array{label: string, count: int}>
     */
    public function openByPriority(): Collection
    {
        $counts = Lead::query()->open()->selectRaw('priority, COUNT(*) as aggregate')->groupBy('priority')->get()->mapWithKeys(fn (Lead $row) => [$row->priority->value => (int) $row->aggregate]);

        return collect(array_reverse(LeadPriority::cases()))->map(fn (LeadPriority $priority) => ['label' => $priority->getLabel(), 'count' => (int) ($counts[$priority->value] ?? 0)]);
    }

    /**
     * @return Collection<int, array{label: string, open: int, won: int}>
     */
    public function teams(): Collection
    {
        $teams = config('markedge.sales.teams', []);

        if ($teams === []) {
            return collect();
        }

        $open = Lead::query()->open()->whereIn('team', $teams)->selectRaw('team, COUNT(*) as aggregate')->groupBy('team')->pluck('aggregate', 'team');
        $won = Lead::query()->where('status', LeadStatus::Converted)->whereIn('team', $teams)
            ->whereNotNull('closed_at')->where('closed_at', '>=', $this->from)->where('closed_at', '<', $this->until)
            ->selectRaw('team, COUNT(*) as aggregate')->groupBy('team')->pluck('aggregate', 'team');

        return collect($teams)->map(fn (string $team) => ['label' => $team, 'open' => (int) ($open[$team] ?? 0), 'won' => (int) ($won[$team] ?? 0)])->values();
    }

    /**
     * Deal values entered by sales on open leads. Never estimated.
     *
     * @return array{leads_with_value: int, open_leads: int, total: string|null, currency: string|null}
     */
    public function enteredValue(): array
    {
        $row = Lead::query()->open()->selectRaw('COUNT(*) as open_leads, COUNT(deal_value) as valued, SUM(deal_value) as total')->first();

        return [
            'leads_with_value' => (int) ($row->valued ?? 0),
            'open_leads' => (int) ($row->open_leads ?? 0),
            'total' => ($row->valued ?? 0) > 0 ? number_format((float) $row->total, 2, '.', ',') : null,
            'currency' => config('markedge.sales.currency'),
        ];
    }

    /**
     * Leads received in the range by their current stage, so stage progression is visible.
     *
     * @return Collection<int, array{label: string, count: int}>
     */
    public function cohortByStage(): Collection
    {
        $counts = $this->newLeads()->selectRaw('status, COUNT(*) as aggregate')->groupBy('status')->get()->mapWithKeys(fn (Lead $row) => [$row->status->value => (int) $row->aggregate]);

        return collect(LeadStatus::cases())->reject(fn (LeadStatus $s) => $s === LeadStatus::Spam)
            ->map(fn (LeadStatus $status) => ['label' => $status->getLabel(), 'count' => (int) ($counts[$status->value] ?? 0)])
            ->filter(fn (array $row) => $row['count'] > 0)->values();
    }

    protected function newLeads(): Builder
    {
        return Lead::query()->notSpam()->where('created_at', '>=', $this->from)->where('created_at', '<', $this->until);
    }

    protected function hoursBetween(string $start, string $end): string
    {
        return DB::connection()->getDriverName() === 'sqlite'
            ? "((julianday({$end}) - julianday({$start})) * 24)"
            : "(TIMESTAMPDIFF(SECOND, {$start}, {$end}) / 3600)";
    }
}
