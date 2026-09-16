<?php

namespace App\Filament\Widgets;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Spatie\Activitylog\Models\Activity;

class RecentActivity extends TableWidget
{
    protected static ?int $sort = 5;

    protected static ?string $heading = 'Recent activity';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return Gate::allows('viewAny', Activity::class);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Activity::query()->with(['causer', 'subject'])->latest())
            ->paginated([8])
            ->columns([
                TextColumn::make('created_at')->label('When')->since(),
                TextColumn::make('causer.name')->label('Who')->placeholder('System'),
                TextColumn::make('description')->limit(40),
                TextColumn::make('subject_type')->label('Subject')->badge()->color('gray')->placeholder('—')
                    ->description(fn (Activity $record): ?string => $record->subject?->title ?? $record->subject?->name ?? null),
            ])
            ->emptyStateHeading('No activity recorded yet');
    }
}
