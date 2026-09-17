<?php

namespace App\Filament\Pages;

use App\Bi\BusinessReport;
use App\Models\Lead;
use App\Models\Product;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use UnitEnum;

/**
 * Role-aware business intelligence. Sections appear according to the viewer's permissions:
 * marketing (leads.export), sales (leads.view_any), product (products.view_any); the executive
 * summary needs marketing and product access together (or Super Admin).
 */
class BusinessIntelligence extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartPie;

    protected static string|UnitEnum|null $navigationGroup = 'Leads';

    protected static ?int $navigationSort = 4;

    protected static ?string $title = 'Business intelligence';

    protected static ?string $slug = 'business-intelligence';

    protected string $view = 'filament.pages.business-intelligence';

    #[Url]
    public string $range = 'last_30';

    #[Url]
    public string $granularity = 'weekly';

    public static function canAccess(): bool
    {
        return Gate::allows('viewAny', Lead::class) || Gate::allows('export', Lead::class) || Gate::allows('viewAny', Product::class);
    }

    public function setRange(string $range): void
    {
        $this->range = array_key_exists($range, BusinessReport::ranges()) ? $range : 'last_30';
    }

    /**
     * @return array<string, bool>
     */
    public static function sections(): array
    {
        $marketing = Gate::allows('export', Lead::class);
        $sales = Gate::allows('viewAny', Lead::class);
        $product = Gate::allows('viewAny', Product::class);

        return [
            'executive' => (auth()->user()?->isSuperAdmin() ?? false) || ($marketing && $product),
            'marketing' => $marketing,
            'sales' => $sales,
            'product' => $product,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        abort_unless(static::canAccess(), 403);

        $report = new BusinessReport($this->range);
        $sections = static::sections();
        $granularity = in_array($this->granularity, ['daily', 'weekly', 'monthly', 'quarterly'], true) ? $this->granularity : 'weekly';

        return [
            'ranges' => BusinessReport::ranges(),
            'sections' => $sections,
            'report' => $report,
            'timezone' => config('app.timezone'),
            'granularity' => $granularity,
            'funnel' => $sections['executive'] || $sections['marketing'] ? $report->businessFunnel() : null,
            'trend' => $sections['executive'] || $sections['marketing'] ? $report->trend($granularity) : collect(),
            'sources' => $sections['marketing'] ? $report->marketing->leadsBySourceMedium('last') : collect(),
            'content' => $sections['marketing'] ? $report->marketing->contentPerformance() : collect(),
            'pipeline' => $sections['sales'] ? $report->sales->pipeline() : [],
            'outcomes' => $sections['sales'] ? $report->sales->outcomes() : null,
            'owners' => $sections['sales'] ? $report->sales->owners() : collect(),
            'products' => $sections['product'] ? $report->productPerformance() : collect(),
        ];
    }
}
