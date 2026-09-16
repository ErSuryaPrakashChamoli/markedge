<?php

namespace App\Filament\Resources\Leads;

use App\Enums\LeadStatus;
use App\Filament\Resources\Leads\Pages\EditLead;
use App\Filament\Resources\Leads\Pages\ListLeads;
use App\Filament\Resources\Leads\Pages\ViewLead;
use App\Models\Lead;
use App\Models\User;
use BackedEnum;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

/**
 * Sales-facing enquiry management. Attribution is read-only here; it is written once at capture.
 */
class LeadResource extends Resource
{
    protected static ?string $model = Lead::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxArrowDown;

    protected static string|UnitEnum|null $navigationGroup = 'Leads';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Enquiries';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationBadge(): ?string
    {
        $count = Lead::query()->where('status', LeadStatus::New)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Workflow')->schema([
                Select::make('status')->options(LeadStatus::class)->required()->native(false),
                Select::make('assigned_to')->label('Owner')->options(fn () => User::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'))->searchable()->native(false),
                DateTimePicker::make('contacted_at')->native(false)->seconds(false),
                DateTimePicker::make('closed_at')->native(false)->seconds(false),
                Textarea::make('notes')->rows(5)->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Lead')->tabs([
                Tab::make('Enquiry')->icon(Heroicon::OutlinedUser)->schema([
                    Section::make('Contact')->schema([
                        TextEntry::make('name'),
                        TextEntry::make('company')->placeholder('—'),
                        TextEntry::make('email')->copyable()->placeholder('—'),
                        TextEntry::make('phone')->copyable()->placeholder('—'),
                        TextEntry::make('country')->placeholder('—'),
                        TextEntry::make('city')->placeholder('—'),
                    ])->columns(3),
                    Section::make('Requirement')->schema([
                        TextEntry::make('requirement')->placeholder('—')->columnSpanFull(),
                        TextEntry::make('message')->placeholder('—')->columnSpanFull(),
                        KeyValueEntry::make('custom_fields')->label('Additional answers')->placeholder('—')->columnSpanFull(),
                    ]),
                    Section::make('Interest')->schema([
                        TextEntry::make('service.name')->label('Service')->placeholder('—'),
                        TextEntry::make('product.name')->label('Product')->placeholder('—'),
                        TextEntry::make('industry.name')->label('Industry')->placeholder('—'),
                        TextEntry::make('solution.name')->label('Solution')->placeholder('—'),
                        TextEntry::make('form.name')->label('Form')->placeholder('—'),
                        TextEntry::make('landingPage.title')->label('Landing page')->placeholder('—'),
                        TextEntry::make('cta.name')->label('CTA')->placeholder('—'),
                        TextEntry::make('submitted_from_url')->label('Submitted from')->placeholder('—'),
                    ])->columns(4),
                    Section::make('Workflow')->schema([
                        TextEntry::make('status')->badge(),
                        TextEntry::make('assignee.name')->label('Owner')->placeholder('Unassigned'),
                        TextEntry::make('created_at')->label('Received')->dateTime('d M Y H:i'),
                        TextEntry::make('contacted_at')->dateTime('d M Y H:i')->placeholder('—'),
                        TextEntry::make('duplicateOf.name')->label('Duplicate of')->placeholder('—'),
                        TextEntry::make('notes')->placeholder('—')->columnSpanFull(),
                    ])->columns(5),
                ]),
                Tab::make('Attribution')->icon(Heroicon::OutlinedFlag)->schema([
                    Section::make('First touch')->schema([
                        TextEntry::make('first_source')->label('Source')->placeholder('—'),
                        TextEntry::make('first_medium')->label('Medium')->placeholder('—'),
                        TextEntry::make('first_campaign')->label('Campaign')->placeholder('—'),
                        TextEntry::make('first_term')->label('Term')->placeholder('—'),
                        TextEntry::make('first_content')->label('Content')->placeholder('—'),
                        TextEntry::make('first_referrer')->label('Referrer')->placeholder('—'),
                        TextEntry::make('first_landing_page')->label('Landing page')->placeholder('—'),
                        TextEntry::make('first_visited_at')->label('First visit')->dateTime('d M Y H:i')->placeholder('—'),
                    ])->columns(4),
                    Section::make('Last touch')->schema([
                        TextEntry::make('last_source')->label('Source')->placeholder('—'),
                        TextEntry::make('last_medium')->label('Medium')->placeholder('—'),
                        TextEntry::make('last_campaign')->label('Campaign')->placeholder('—'),
                        TextEntry::make('last_term')->label('Term')->placeholder('—'),
                        TextEntry::make('last_content')->label('Content')->placeholder('—'),
                        TextEntry::make('last_referrer')->label('Referrer')->placeholder('—'),
                        TextEntry::make('last_landing_page')->label('Landing page')->placeholder('—'),
                        TextEntry::make('last_visited_at')->label('Last visit')->dateTime('d M Y H:i')->placeholder('—'),
                    ])->columns(4),
                    Section::make('Campaign match')->schema([
                        TextEntry::make('campaign.name')->label('Matched campaign')->placeholder('No campaign matched'),
                    ]),
                ]),
                Tab::make('Technical')->icon(Heroicon::OutlinedCpuChip)
                    ->visible(fn (): bool => auth()->user()?->isSuperAdmin() ?? false)
                    ->schema([
                        Section::make()->schema([
                            TextEntry::make('device_type')->placeholder('—'),
                            TextEntry::make('browser')->placeholder('—'),
                            TextEntry::make('os')->label('OS')->placeholder('—'),
                            TextEntry::make('locale')->placeholder('—'),
                            TextEntry::make('visitor_id')->placeholder('—'),
                            TextEntry::make('ip')->label('IP')->placeholder('Not stored'),
                            TextEntry::make('user_agent')->placeholder('—')->columnSpanFull(),
                            TextEntry::make('consent_given_at')->dateTime('d M Y H:i')->placeholder('—'),
                            TextEntry::make('spam_score'),
                        ])->columns(3),
                    ]),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['product', 'service', 'campaign', 'form', 'assignee']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')->searchable()->sortable()->description(fn (Lead $record): ?string => $record->company),
                TextColumn::make('email')->searchable()->copyable()->placeholder('—')->description(fn (Lead $record): ?string => $record->phone),
                TextColumn::make('interest')->label('Interest')->state(fn (Lead $record): string => $record->product?->name ?? $record->service?->name ?? '—'),
                TextColumn::make('last_source')->label('Source')->badge()->color('gray')->placeholder('—')->toggleable(),
                TextColumn::make('campaign.name')->label('Campaign')->placeholder('—')->toggleable(),
                TextColumn::make('form.name')->label('Form')->placeholder('—')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('assignee.name')->label('Owner')->placeholder('—')->toggleable(),
                TextColumn::make('created_at')->label('Received')->dateTime('d M Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(LeadStatus::class)->multiple(),
                SelectFilter::make('form_id')->label('Form')->relationship('form', 'name')->preload(),
                SelectFilter::make('campaign_id')->label('Campaign')->relationship('campaign', 'name')->preload(),
                SelectFilter::make('service_id')->label('Service')->relationship('service', 'name')->preload(),
                SelectFilter::make('product_id')->label('Product')->relationship('product', 'name')->preload(),
                SelectFilter::make('industry_id')->label('Industry')->relationship('industry', 'name')->preload(),
                SelectFilter::make('assigned_to')->label('Owner')->relationship('assignee', 'name')->preload(),
                SelectFilter::make('last_source')->label('Source')->options(fn (): array => Lead::query()->whereNotNull('last_source')->distinct()->orderBy('last_source')->pluck('last_source', 'last_source')->all()),
                Filter::make('created_at')->schema([
                    DatePicker::make('from')->native(false),
                    DatePicker::make('until')->native(false),
                ])->query(fn (Builder $query, array $data): Builder => $query
                    ->when($data['from'] ?? null, fn (Builder $q, string $date) => $q->whereDate('created_at', '>=', $date))
                    ->when($data['until'] ?? null, fn (Builder $q, string $date) => $q->whereDate('created_at', '<=', $date))),
                TrashedFilter::make(),
            ])
            ->recordActions([ViewAction::make(), EditAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('assign')
                        ->label('Assign owner')
                        ->icon(Heroicon::OutlinedUser)
                        ->schema([Select::make('assigned_to')->label('Owner')->options(fn () => User::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'))->required()->native(false)])
                        ->action(function (Collection $records, array $data): void {
                            $records->each(fn (Lead $lead) => Gate::allows('update', $lead) && $lead->update(['assigned_to' => $data['assigned_to']]));
                            Notification::make()->title('Owner assigned.')->success()->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('markSpam')
                        ->label('Mark as spam')
                        ->icon(Heroicon::OutlinedShieldCheck)
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $records->each(fn (Lead $lead) => Gate::allows('update', $lead) && $lead->update(['status' => LeadStatus::Spam]));
                            Notification::make()->title('Marked as spam.')->success()->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('exportCsv')
                        ->label('Export CSV')
                        ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                        ->authorize(fn (): bool => Gate::allows('export', Lead::class))
                        ->action(fn (Collection $records): StreamedResponse => static::exportCsv($records)),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    protected static function exportCsv(Collection $records): StreamedResponse
    {
        activity('content')->causedBy(auth()->user())->withProperties(['count' => $records->count()])->event('exported')->log('Leads exported');

        $columns = ['id', 'created_at', 'name', 'company', 'email', 'phone', 'country', 'city', 'status', 'requirement',
            'first_source', 'first_medium', 'first_campaign', 'last_source', 'last_medium', 'last_campaign'];

        return response()->streamDownload(function () use ($records, $columns): void {
            $handle = fopen('php://output', 'wb');
            fputcsv($handle, $columns);

            foreach ($records as $lead) {
                fputcsv($handle, array_map(fn (string $column) => (string) ($lead->{$column} instanceof BackedEnum ? $lead->{$column}->value : $lead->{$column}), $columns));
            }

            fclose($handle);
        }, 'leads-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLeads::route('/'),
            'view' => ViewLead::route('/{record}'),
            'edit' => EditLead::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
