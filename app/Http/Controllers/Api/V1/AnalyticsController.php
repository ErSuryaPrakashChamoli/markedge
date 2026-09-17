<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Reports\ConversionReport;
use App\Reports\MarketingReport;
use App\Sales\SalesReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Aggregates only. No identifiers, no personal data.
 */
class AnalyticsController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $range = (string) $request->query('range', 'last_30');
        $range = array_key_exists($range, ConversionReport::ranges()) && $range !== 'custom' ? $range : 'last_30';
        $marketing = MarketingReport::forRange($range);
        $sales = SalesReport::forRange($range);

        return response()->json([
            'range' => $range,
            'from' => $marketing->from->toDateString(),
            'until' => $marketing->until->copy()->subDay()->toDateString(),
            'timezone' => config('app.timezone'),
            'traffic' => $marketing->traffic(),
            'funnel' => $marketing->funnel(),
            'sales' => ['pipeline' => $sales->pipeline(), 'outcomes' => $sales->outcomes()],
        ]);
    }
}
