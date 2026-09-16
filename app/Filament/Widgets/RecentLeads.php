<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Leads\LeadResource;
use App\Models\Lead;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

class RecentLeads extends TableWidget
{
    protected static ?int $sort = 4;

    protected static ?string $heading = 'Recent enquiries';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return Gate::allows('viewAny', Lead::class);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Lead::query()->notSpam()->with(['product', 'service', 'campaign'])->latest())
            ->paginated([8])
            ->columns([
                TextColumn::make('name')->description(fn (Lead $record): ?string => $record->company),
                TextColumn::make('interest')->label('Interest')->state(fn (Lead $record): string => $record->product?->name ?? $record->service?->name ?? '—'),
                TextColumn::make('last_source')->label('Source')->badge()->color('gray')->placeholder('—'),
                TextColumn::make('campaign.name')->label('Campaign')->placeholder('—'),
                TextColumn::make('status')->badge(),
                TextColumn::make('created_at')->label('Received')->since(),
            ])
            ->recordUrl(fn (Lead $record): string => LeadResource::getUrl('view', ['record' => $record]))
            ->emptyStateHeading('No enquiries yet')
            ->emptyStateDescription('Submissions from website forms appear here.');
    }
}
