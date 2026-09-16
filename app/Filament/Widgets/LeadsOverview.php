<?php

namespace App\Filament\Widgets;

use App\Enums\LeadStatus;
use App\Models\Campaign;
use App\Models\Lead;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Gate;

class LeadsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 3;

    protected ?string $heading = 'Enquiries';

    public static function canView(): bool
    {
        return Gate::allows('viewAny', Lead::class);
    }

    protected function getStats(): array
    {
        $base = Lead::query()->notSpam();

        $stats = [
            Stat::make('New enquiries', (clone $base)->where('status', LeadStatus::New)->count())->description('waiting for first contact'),
            Stat::make('This week', (clone $base)->where('created_at', '>=', now()->startOfWeek())->count()),
            Stat::make('This month', (clone $base)->where('created_at', '>=', now()->startOfMonth())->count()),
        ];

        if (Gate::allows('viewAny', Campaign::class)) {
            $stats[] = Stat::make('Active campaigns', Campaign::query()->active()->count());
        }

        return $stats;
    }
}
