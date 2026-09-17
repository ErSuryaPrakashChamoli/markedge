<?php

namespace App\Filament\Pages;

use App\Models\Lead;
use App\Reports\ConversionReport;
use App\Reports\MarketingReport;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use UnitEnum;

/**
 * First-party conversion reporting: factual counts by source, campaign, form, content and CTA.
 * Access follows the leads.export permission (business roles), never the ordinary sales workflow.
 */
class ConversionReports extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartLine;

    protected static string|UnitEnum|null $navigationGroup = 'Leads';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Marketing analytics';

    protected string $view = 'filament.pages.conversion-reports';

    #[Url]
    public string $range = 'last_30';

    #[Url]
    public ?string $from = null;

    #[Url]
    public ?string $until = null;

    #[Url]
    public string $granularity = 'daily';

    public static function canAccess(): bool
    {
        return Gate::allows('export', Lead::class);
    }

    public function setRange(string $range): void
    {
        $this->range = array_key_exists($range, ConversionReport::ranges()) ? $range : 'last_30';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        abort_unless(static::canAccess(), 403);

        $report = MarketingReport::forRange($this->range, $this->from, $this->until);
        $granularity = in_array($this->granularity, ['daily', 'weekly', 'monthly', 'quarterly'], true) ? $this->granularity : 'daily';

        return [
            'ranges' => ConversionReport::ranges(),
            'report' => $report,
            'timezone' => config('app.timezone'),
            'totals' => $report->totals(),
            'traffic' => $report->traffic(),
            'funnel' => $report->funnel(),
            'trend' => $report->trend($granularity),
            'granularity' => $granularity,
            'paths' => $report->conversionPaths(),
            'content' => $report->contentPerformance(),
            'productInterest' => $report->interest('product', 'products', 'product_id'),
            'serviceInterest' => $report->interest('service', 'services', 'service_id'),
            'sections' => [
                'Sessions by landing source / medium' => $report->sessionsBySource(),
                'Top pages (views)' => $report->topPages(),
                'Landing pages (sessions)' => $report->landingPages(),
                'Leads by first-touch source / medium' => $report->leadsBySourceMedium('first'),
                'Leads by last-touch source / medium' => $report->leadsBySourceMedium('last'),
                'Leads by last-touch campaign (UTM)' => $report->leadsBy('last_campaign', 'No campaign'),
                'Leads by matched campaign' => $report->leadsByRelation('campaigns', 'campaign_id'),
                'Leads by form' => $report->leadsByRelation('forms', 'form_id'),
                'Leads by service' => $report->leadsByRelation('services', 'service_id'),
                'Leads by product' => $report->leadsByRelation('products', 'product_id'),
                'Leads by solution' => $report->leadsByRelation('solutions', 'solution_id'),
                'Leads by first landing page' => $report->leadsBy('first_landing_page', 'Unknown'),
                'Leads by conversion page' => $report->leadsBy('submitted_from_url', 'Unknown'),
                'CTA clicks by CTA' => $report->ctaClicks(),
                'CTA clicks by page' => $report->ctaClicksByPage(),
            ],
            'searches' => Gate::allows('seo.view_any') || (auth()->user()?->isSuperAdmin() ?? false) ? $report->topSearches() : null,
        ];
    }
}
