<?php

namespace App\Filament\Resources\NewsletterSubscribers;

use App\Enums\SubscriberStatus;
use App\Filament\Resources\NewsletterSubscribers\Pages\ManageNewsletterSubscribers;
use App\Models\NewsletterSubscriber;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

/**
 * Newsletter sign-ups from the website. Sending happens in an external email service:
 * marketing exports the subscribed list as CSV and imports it there.
 */
class NewsletterSubscriberResource extends Resource
{
    protected static ?string $model = NewsletterSubscriber::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelopeOpen;

    protected static string|UnitEnum|null $navigationGroup = 'Marketing';

    protected static ?int $navigationSort = 9;

    protected static ?string $navigationLabel = 'Newsletter subscribers';

    protected static ?string $recordTitleAttribute = 'email';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('email')->email()->required()->maxLength(255)->unique(ignoreRecord: true),
            TextInput::make('name')->maxLength(120),
            Select::make('status')->options(SubscriberStatus::class)->default(SubscriberStatus::Subscribed->value)->required()->native(false),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('email')->searchable()->sortable()->copyable(),
                TextColumn::make('name')->searchable()->placeholder('—')->toggleable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('source_path')->label('Signed up on')->placeholder('Added by admin')->toggleable(),
                TextColumn::make('subscribed_at')->label('Subscribed')->dateTime('d M Y H:i')->sortable()->placeholder('—'),
                TextColumn::make('unsubscribed_at')->label('Unsubscribed')->dateTime('d M Y H:i')->sortable()->placeholder('—')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->options(SubscriberStatus::class),
            ])
            ->recordActions([
                Action::make('unsubscribe')->label('Unsubscribe')->icon(Heroicon::OutlinedNoSymbol)->color('gray')->requiresConfirmation()
                    ->visible(fn (NewsletterSubscriber $record): bool => $record->status === SubscriberStatus::Subscribed)
                    ->authorize(fn (NewsletterSubscriber $record): bool => Gate::allows('update', $record))
                    ->action(fn (NewsletterSubscriber $record) => $record->unsubscribe()),
                Action::make('resubscribe')->label('Resubscribe')->icon(Heroicon::OutlinedArrowPath)->color('gray')->requiresConfirmation()
                    ->visible(fn (NewsletterSubscriber $record): bool => $record->status === SubscriberStatus::Unsubscribed)
                    ->authorize(fn (NewsletterSubscriber $record): bool => Gate::allows('update', $record))
                    ->action(fn (NewsletterSubscriber $record) => $record->resubscribe()),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('exportCsv')
                        ->label('Export CSV')
                        ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                        ->authorize(fn (): bool => Gate::allows('export', NewsletterSubscriber::class))
                        ->action(fn (Collection $records): StreamedResponse => static::exportCsv($records)),
                    BulkAction::make('unsubscribe')
                        ->label('Unsubscribe')
                        ->icon(Heroicon::OutlinedNoSymbol)
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $records->each(fn (NewsletterSubscriber $subscriber) => $subscriber->status === SubscriberStatus::Subscribed && Gate::allows('update', $subscriber) && $subscriber->unsubscribe());
                            Notification::make()->title('Unsubscribed.')->success()->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function exportCsv(Collection $records): StreamedResponse
    {
        activity('content')->causedBy(auth()->user())->withProperties(['count' => $records->count()])->event('exported')->log('Newsletter subscribers exported');

        $columns = ['email', 'name', 'status', 'subscribed_at', 'unsubscribed_at', 'source_path', 'consent_given_at'];

        return response()->streamDownload(function () use ($records, $columns): void {
            $handle = fopen('php://output', 'wb');
            fputcsv($handle, $columns);

            foreach ($records as $subscriber) {
                fputcsv($handle, array_map(function (string $column) use ($subscriber): string {
                    $value = $subscriber->{$column};

                    return (string) ($value instanceof BackedEnum ? $value->value : $value);
                }, $columns));
            }

            fclose($handle);
        }, 'newsletter-subscribers-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageNewsletterSubscribers::route('/'),
        ];
    }
}
