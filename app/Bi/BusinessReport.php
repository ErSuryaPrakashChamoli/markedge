<?php

namespace App\Bi;

use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\Product;
use App\Reports\ConversionReport;
use App\Reports\MarketingReport;
use App\Sales\SalesReport;
use Illuminate\Support\Collection;

/**
 * Cross-functional aggregates for the Business intelligence page (Phase 16). Composes the Phase 13
 * marketing report and the Phase 14 sales report; every trend bucket is computed in the application
 * timezone by the same bucket expression so marketing and sales lines line up.
 */
class BusinessReport
{
    public readonly MarketingReport $marketing;

    public readonly SalesReport $sales;

    public function __construct(public readonly string $range, ?string $from = null, ?string $until = null)
    {
        $this->marketing = MarketingReport::forRange($range, $from, $until);
        $this->sales = new SalesReport($this->marketing->from, $this->marketing->until);
    }

    /**
     * Sessions → leads → qualified → won, with rates only where a denominator exists.
     *
     * @return array<string, array{count: int, rate: float|null}>
     */
    public function businessFunnel(): array
    {
        $funnel = $this->marketing->funnel();
        $won = $this->marketing->leads()->where('status', LeadStatus::Converted->value)->count();
        $leads = $funnel['leads']['count'];

        return [
            'sessions' => $funnel['sessions'],
            'leads' => $funnel['leads'],
            'qualified' => $funnel['qualified'],
            'won' => ['count' => $won, 'rate' => $leads > 0 ? round($won / $leads * 100, 1) : null],
        ];
    }

    /**
     * Marketing trend plus leads won per bucket (by close date).
     *
     * @return Collection<int, array{period: string, page_views: int, sessions: int, leads: int, won: int}>
     */
    public function trend(string $granularity): Collection
    {
        $granularity = in_array($granularity, ['daily', 'weekly', 'monthly', 'quarterly'], true) ? $granularity : 'daily';
        $trend = $this->marketing->trend($granularity);
        $won = $this->wonByBucket($granularity);

        return $trend->map(fn (array $row) => $row + ['won' => (int) ($won[$row['period']] ?? 0)])->values();
    }

    /**
     * @return Collection<int, array{label: string, views: int, leads: int, won: int}>
     */
    public function productPerformance(): Collection
    {
        $interest = $this->marketing->interest('product', 'products', 'product_id')->keyBy('label');
        $won = Lead::query()->where('status', LeadStatus::Converted->value)->whereNotNull('product_id')
            ->whereNotNull('closed_at')->where('closed_at', '>=', $this->marketing->from)->where('closed_at', '<', $this->marketing->until)
            ->selectRaw('product_id, COUNT(*) as aggregate')->groupBy('product_id')->pluck('aggregate', 'product_id');
        $names = $won->isEmpty() ? collect() : Product::query()->whereIn('id', $won->keys())->pluck('name', 'id');

        $wonByLabel = [];

        foreach ($won as $productId => $count) {
            $label = (string) ($names[$productId] ?? "Product #{$productId}");
            $wonByLabel[$label] = (int) $count;
            $interest->put($label, $interest->get($label, ['label' => $label, 'views' => 0, 'leads' => 0]));
        }

        return $interest->map(fn (array $row) => $row + ['won' => $wonByLabel[$row['label']] ?? 0])->values();
    }

    /**
     * @return array<string, int>
     */
    protected function wonByBucket(string $granularity): array
    {
        $bucket = $this->marketing->bucketExpression($granularity, 'closed_at');

        return Lead::query()->where('status', LeadStatus::Converted->value)->whereNotNull('closed_at')
            ->where('closed_at', '>=', $this->marketing->from)->where('closed_at', '<', $this->marketing->until)
            ->selectRaw("{$bucket} as period, COUNT(*) as aggregate")->groupBy('period')->pluck('aggregate', 'period')->all();
    }

    /**
     * @return array<string, string>
     */
    public static function ranges(): array
    {
        return collect(ConversionReport::ranges())->except('custom')->all();
    }
}
