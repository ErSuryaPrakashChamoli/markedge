<?php

namespace App\Filament\Pages;

use App\Enums\LeadStatus;
use App\Models\Lead;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use UnitEnum;

/**
 * Where enquiries come from, derived from stored first/last-touch attribution.
 */
class LeadSources extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartLine;

    protected static string|UnitEnum|null $navigationGroup = 'Leads';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.lead-sources';

    public int $days = 30;

    public static function canAccess(): bool
    {
        return Gate::allows('viewAny', Lead::class);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            'total' => $this->baseQuery()->count(),
            'firstTouch' => $this->groupBy('first_source', 'first_medium'),
            'lastTouch' => $this->groupBy('last_source', 'last_medium'),
            'campaigns' => $this->baseQuery()->whereNotNull('campaign_id')->with('campaign')->get()->groupBy('campaign_id')
                ->map(fn (Collection $leads) => ['name' => $leads->first()->campaign?->name ?? '—', 'count' => $leads->count()])
                ->sortByDesc('count')->values(),
            'forms' => $this->baseQuery()->whereNotNull('form_id')->with('form')->get()->groupBy('form_id')
                ->map(fn (Collection $leads) => ['name' => $leads->first()->form?->name ?? '—', 'count' => $leads->count()])
                ->sortByDesc('count')->values(),
        ];
    }

    protected function baseQuery(): Builder
    {
        return Lead::query()->where('status', '!=', LeadStatus::Spam)->where('created_at', '>=', now()->subDays($this->days));
    }

    /**
     * @return Collection<int, array{source: string, medium: string, count: int}>
     */
    protected function groupBy(string $sourceColumn, string $mediumColumn): Collection
    {
        return $this->baseQuery()
            ->selectRaw("COALESCE({$sourceColumn}, 'direct') as source, COALESCE({$mediumColumn}, 'none') as medium, COUNT(*) as aggregate")
            ->groupBy('source', 'medium')
            ->orderByDesc('aggregate')
            ->get()
            ->map(fn ($row) => ['source' => $row->source, 'medium' => $row->medium, 'count' => (int) $row->aggregate]);
    }
}
