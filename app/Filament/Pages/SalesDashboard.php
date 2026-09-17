<?php

namespace App\Filament\Pages;

use App\Models\Lead;
use App\Models\LeadFollowUp;
use App\Reports\ConversionReport;
use App\Sales\SalesReport;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use UnitEnum;

/**
 * Sales performance from stored leads only: pipeline, outcomes, owners, follow-ups and SLA.
 * Money appears only where sales entered a value; rates only where a denominator exists.
 */
class SalesDashboard extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'Leads';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Sales dashboard';

    protected static ?string $slug = 'sales-dashboard';

    protected string $view = 'filament.pages.sales-dashboard';

    #[Url]
    public string $range = 'this_month';

    public static function canAccess(): bool
    {
        return Gate::allows('viewAny', Lead::class);
    }

    public function setRange(string $range): void
    {
        $this->range = array_key_exists($range, ConversionReport::ranges()) && $range !== 'custom' ? $range : 'this_month';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        abort_unless(static::canAccess(), 403);

        $report = SalesReport::forRange($this->range);

        return [
            'ranges' => collect(ConversionReport::ranges())->except('custom')->all(),
            'report' => $report,
            'timezone' => config('app.timezone'),
            'pipeline' => $report->pipeline(),
            'outcomes' => $report->outcomes(),
            'firstContact' => $report->firstContact(),
            'followUps' => $report->followUps(),
            'myFollowUps' => LeadFollowUp::query()->open()->ownedBy((int) auth()->id())->with('lead')->orderBy('due_at')->limit(10)->get(),
            'owners' => $report->owners(),
            'lostReasons' => $report->lostReasons(),
            'priorities' => $report->openByPriority(),
            'teams' => $report->teams(),
            'teamsConfigured' => config('markedge.sales.teams', []) !== [],
            'value' => $report->enteredValue(),
            'cohort' => $report->cohortByStage(),
        ];
    }
}
