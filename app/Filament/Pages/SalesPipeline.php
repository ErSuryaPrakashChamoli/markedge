<?php

namespace App\Filament\Pages;

use App\Enums\LeadPriority;
use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\User;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use UnitEnum;

/**
 * Board view of open leads by stage. Cards link to the enquiry; nothing changes from here.
 */
class SalesPipeline extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedViewColumns;

    protected static string|UnitEnum|null $navigationGroup = 'Leads';

    protected static ?int $navigationSort = 0;

    protected static ?string $title = 'Sales pipeline';

    protected static ?string $slug = 'sales-pipeline';

    protected string $view = 'filament.pages.sales-pipeline';

    #[Url]
    public string $owner = 'all';

    #[Url]
    public string $team = '';

    #[Url]
    public string $priority = '';

    public static function canAccess(): bool
    {
        return Gate::allows('viewAny', Lead::class);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        abort_unless(static::canAccess(), 403);

        $limit = max(5, (int) config('markedge.sales.pipeline_column_limit', 50));
        $counts = $this->filtered()->selectRaw('status, COUNT(*) as aggregate')->groupBy('status')->get()->mapWithKeys(fn (Lead $row) => [$row->status->value => (int) $row->aggregate]);
        $columns = [];

        foreach (LeadStatus::pipeline() as $status) {
            $columns[] = [
                'status' => $status,
                'count' => (int) ($counts[$status->value] ?? 0),
                'leads' => $this->filtered()->where('status', $status)->with(['assignee', 'product', 'service'])
                    ->orderByRaw("CASE priority WHEN 'urgent' THEN 0 WHEN 'high' THEN 1 WHEN 'normal' THEN 2 ELSE 3 END")
                    ->orderBy('stage_entered_at')->limit($limit)->get(),
            ];
        }

        return [
            'columns' => $columns,
            'limit' => $limit,
            'owners' => User::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'),
            'teams' => config('markedge.sales.teams', []),
            'priorities' => LeadPriority::cases(),
            'slaHours' => config('markedge.sales.sla.first_contact_hours'),
        ];
    }

    protected function filtered(): Builder
    {
        return Lead::query()->open()
            ->when($this->owner === 'mine', fn (Builder $q) => $q->where('assigned_to', auth()->id()))
            ->when($this->owner === 'unassigned', fn (Builder $q) => $q->whereNull('assigned_to'))
            ->when(ctype_digit($this->owner), fn (Builder $q) => $q->where('assigned_to', (int) $this->owner))
            ->when($this->team !== '', fn (Builder $q) => $q->where('team', $this->team))
            ->when(LeadPriority::tryFrom($this->priority) !== null, fn (Builder $q) => $q->where('priority', $this->priority));
    }
}
