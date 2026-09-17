<?php

namespace App\Filament\Pages;

use App\Models\AutomationRun;
use App\Ops\OperationsStatus;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Operational dashboard for Super Admins: dependencies, queue, automation, channels, API, data.
 */
class Operations extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedServerStack;

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 0;

    protected static ?string $title = 'Operations';

    protected static ?string $slug = 'operations';

    protected string $view = 'filament.pages.operations';

    public static function canAccess(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        abort_unless(static::canAccess(), 403);

        $status = app(OperationsStatus::class);

        return [
            'dependencies' => $status->dependencies(),
            'queue' => $status->queue(),
            'automation' => $status->automation(),
            'channels' => $status->channels(),
            'api' => $status->api(),
            'data' => $status->data(),
            'environment' => $status->environment(),
            'recentFailures' => AutomationRun::query()->with('rule')->where('status', 'failed')->latest('id')->limit(10)->get(),
        ];
    }
}
