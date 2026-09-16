<?php

namespace App\Filament\Resources\Activities;

use App\Filament\Resources\Activities\Pages\ListActivities;
use App\Filament\Resources\Activities\Pages\ViewActivity;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\Activity;
use UnitEnum;

/**
 * Read-only audit trail (architecture §37).
 */
class ActivityResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Activity log';

    protected static ?string $modelLabel = 'activity';

    protected static ?string $pluralModelLabel = 'activity log';

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->schema([
                TextEntry::make('created_at')->label('When')->dateTime('d M Y H:i:s'),
                TextEntry::make('causer.name')->label('Who')->placeholder('System'),
                TextEntry::make('event')->badge()->placeholder('—'),
                TextEntry::make('description'),
                TextEntry::make('subject_type')->label('Subject type')->placeholder('—'),
                TextEntry::make('subject_id')->label('Subject id')->placeholder('—'),
                TextEntry::make('log_name')->label('Log')->badge()->color('gray'),
            ])->columns(3),
            Section::make('Changes')->schema([
                KeyValueEntry::make('properties.old')->label('Before')->placeholder('—'),
                KeyValueEntry::make('properties.attributes')->label('After')->placeholder('—'),
                KeyValueEntry::make('properties')->label('Details')->placeholder('—')
                    ->visible(fn (Activity $record): bool => ! isset($record->properties['attributes']) && ! isset($record->properties['old'])),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['causer', 'subject']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('When')->dateTime('d M Y H:i')->sortable(),
                TextColumn::make('causer.name')->label('Who')->placeholder('System'),
                TextColumn::make('event')->badge()->placeholder('—'),
                TextColumn::make('description')->limit(50),
                TextColumn::make('subject_type')->label('Subject')->badge()->color('gray')->placeholder('—')
                    ->description(fn (Activity $record): ?string => $record->subject?->title ?? $record->subject?->name ?? ($record->subject_id ? "#{$record->subject_id}" : null)),
            ])
            ->filters([
                SelectFilter::make('subject_type')->label('Subject')->options(fn (): array => Activity::query()->whereNotNull('subject_type')->distinct()->orderBy('subject_type')->pluck('subject_type', 'subject_type')->all()),
                SelectFilter::make('event')->options(fn (): array => Activity::query()->whereNotNull('event')->distinct()->orderBy('event')->pluck('event', 'event')->all()),
                SelectFilter::make('causer_id')->label('User')->relationship('causer', 'name')->preload(),
                Filter::make('created_at')->schema([DatePicker::make('from')->native(false), DatePicker::make('until')->native(false)])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $q, string $date) => $q->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $q, string $date) => $q->whereDate('created_at', '<=', $date))),
            ])
            ->recordActions([ViewAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListActivities::route('/'),
            'view' => ViewActivity::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
