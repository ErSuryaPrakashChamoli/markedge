<?php

namespace App\Filament\Support;

use App\Enums\PublishStatus;
use App\Services\Cms\Publisher;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Workflow actions shared by every publishable resource. Each one is authorised by the
 * model policy (update for review, publish for everything else) and runs through the Publisher.
 */
class PublishActions
{
    /**
     * @return array<int, Action>
     */
    public static function record(): array
    {
        return [
            Action::make('submitForReview')
                ->label('Submit for review')
                ->icon(Heroicon::OutlinedPaperAirplane)
                ->color('gray')
                ->visible(fn (Model $record): bool => $record->status === PublishStatus::Draft && Gate::allows('update', $record))
                ->action(fn (Model $record, Publisher $publisher) => static::run(fn () => $publisher->submitForReview($record), 'Submitted for review.')),

            Action::make('publish')
                ->label('Publish')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->requiresConfirmation()
                ->modalDescription('The content becomes visible on the public website immediately.')
                ->visible(fn (Model $record): bool => $record->status !== PublishStatus::Published && Gate::allows('publish', $record))
                ->action(fn (Model $record, Publisher $publisher) => static::run(fn () => $publisher->publish($record), 'Published.')),

            Action::make('schedule')
                ->label('Schedule')
                ->icon(Heroicon::OutlinedClock)
                ->color('info')
                ->visible(fn (Model $record): bool => $record->status !== PublishStatus::Published && Gate::allows('publish', $record))
                ->schema([
                    DateTimePicker::make('publish_at')->label('Publish at')->required()->native(false)->seconds(false)->minDate(now()),
                ])
                ->action(fn (Model $record, array $data, Publisher $publisher) => static::run(
                    fn () => $publisher->schedule($record, Carbon::parse($data['publish_at'])),
                    'Scheduled.',
                )),

            Action::make('unpublish')
                ->label('Unpublish')
                ->icon(Heroicon::OutlinedEyeSlash)
                ->color('warning')
                ->requiresConfirmation()
                ->visible(fn (Model $record): bool => in_array($record->status, [PublishStatus::Published, PublishStatus::Scheduled], true) && Gate::allows('publish', $record))
                ->action(fn (Model $record, Publisher $publisher) => static::run(fn () => $publisher->unpublish($record), 'Unpublished. The record is a draft again.')),

            Action::make('archive')
                ->label('Archive')
                ->icon(Heroicon::OutlinedArchiveBox)
                ->color('danger')
                ->requiresConfirmation()
                ->modalDescription('Archived content is removed from the website and its URL returns 410 Gone.')
                ->visible(fn (Model $record): bool => $record->status !== PublishStatus::Archived && Gate::allows('publish', $record))
                ->action(fn (Model $record, Publisher $publisher) => static::run(fn () => $publisher->archive($record), 'Archived.')),
        ];
    }

    /**
     * @return array<int, BulkAction>
     */
    public static function bulk(): array
    {
        return [
            BulkAction::make('publishSelected')
                ->label('Publish selected')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->requiresConfirmation()
                ->deselectRecordsAfterCompletion()
                ->action(fn (Collection $records, Publisher $publisher) => static::runBulk($records, fn (Model $record) => $publisher->publish($record), 'published')),

            BulkAction::make('unpublishSelected')
                ->label('Unpublish selected')
                ->icon(Heroicon::OutlinedEyeSlash)
                ->color('warning')
                ->requiresConfirmation()
                ->deselectRecordsAfterCompletion()
                ->action(fn (Collection $records, Publisher $publisher) => static::runBulk($records, fn (Model $record) => $publisher->unpublish($record), 'unpublished')),

            BulkAction::make('archiveSelected')
                ->label('Archive selected')
                ->icon(Heroicon::OutlinedArchiveBox)
                ->color('danger')
                ->requiresConfirmation()
                ->deselectRecordsAfterCompletion()
                ->action(fn (Collection $records, Publisher $publisher) => static::runBulk($records, fn (Model $record) => $publisher->archive($record), 'archived')),
        ];
    }

    protected static function run(\Closure $callback, string $success): void
    {
        try {
            $callback();
            Notification::make()->title($success)->success()->send();
        } catch (ValidationException $exception) {
            Notification::make()
                ->title('Cannot publish yet')
                ->body(implode(' ', collect($exception->errors())->flatten()->all()))
                ->danger()
                ->persistent()
                ->send();
        }
    }

    /**
     * Applies the transition only to records the user may publish; reports skipped ones.
     */
    protected static function runBulk(Collection $records, \Closure $callback, string $verb): void
    {
        $done = 0;
        $skipped = 0;
        $failed = [];

        foreach ($records as $record) {
            if (! Gate::allows('publish', $record)) {
                $skipped++;

                continue;
            }

            try {
                $callback($record);
                $done++;
            } catch (ValidationException $exception) {
                $failed[] = ($record->title ?? $record->name ?? $record->getKey()).': '.collect($exception->errors())->flatten()->first();
            }
        }

        $notification = Notification::make()->title(ucfirst("{$done} record(s) {$verb}."));

        if ($skipped > 0 || $failed !== []) {
            $notification->body(trim(($skipped > 0 ? "{$skipped} skipped (no permission). " : '').implode(' ', $failed)))->warning();
        } else {
            $notification->success();
        }

        $notification->send();
    }
}
