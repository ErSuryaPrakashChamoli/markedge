<?php

namespace App\Filament\Support;

use App\Enums\PublishStatus;
use App\Models\User;
use App\Services\Cms\Publisher;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Auth\Access\AuthorizationException;
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
                    DateTimePicker::make('unpublish_at')->label('Unpublish at')->native(false)->seconds(false)->after('publish_at')->helperText('Optional: returns to Draft at this time.'),
                ])
                ->action(fn (Model $record, array $data, Publisher $publisher) => static::run(
                    fn () => $publisher->schedule($record, Carbon::parse($data['publish_at']), filled($data['unpublish_at'] ?? null) ? Carbon::parse($data['unpublish_at']) : null),
                    'Scheduled.',
                )),

            Action::make('restoreFromArchive')
                ->label('Restore to draft')
                ->icon(Heroicon::OutlinedArrowUturnLeft)
                ->color('gray')
                ->requiresConfirmation()
                ->visible(fn (Model $record): bool => $record->status === PublishStatus::Archived && Gate::allows('publish', $record))
                ->action(fn (Model $record, Publisher $publisher) => static::run(fn () => $publisher->restore($record), 'Restored as a draft.')),

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
            BulkAction::make('submitForReviewSelected')
                ->label('Submit selected for review')
                ->icon(Heroicon::OutlinedPaperAirplane)
                ->color('gray')
                ->requiresConfirmation()
                ->deselectRecordsAfterCompletion()
                ->action(fn (Collection $records, Publisher $publisher) => static::runBulk($records, fn (Model $record) => $publisher->submitForReview($record), 'submitted for review', 'update')),

            BulkAction::make('scheduleSelected')
                ->label('Schedule selected')
                ->icon(Heroicon::OutlinedClock)
                ->color('info')
                ->schema([
                    DateTimePicker::make('publish_at')->label('Publish at')->required()->native(false)->seconds(false)->minDate(now()),
                    DateTimePicker::make('unpublish_at')->label('Unpublish at')->native(false)->seconds(false)->after('publish_at'),
                ])
                ->deselectRecordsAfterCompletion()
                ->action(fn (Collection $records, array $data, Publisher $publisher) => static::runBulk(
                    $records,
                    fn (Model $record) => $publisher->schedule($record, Carbon::parse($data['publish_at']), filled($data['unpublish_at'] ?? null) ? Carbon::parse($data['unpublish_at']) : null),
                    'scheduled',
                )),

            BulkAction::make('assignSelected')
                ->label('Assign owner / reviewer')
                ->icon(Heroicon::OutlinedUserPlus)
                ->color('gray')
                ->schema([
                    Select::make('owner_id')->label('Owner')->options(fn () => User::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'))->searchable()->native(false),
                    Select::make('reviewer_id')->label('Reviewer')->options(fn () => User::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'))->searchable()->native(false),
                ])
                ->deselectRecordsAfterCompletion()
                ->action(fn (Collection $records, array $data, Publisher $publisher) => static::runBulk(
                    $records,
                    fn (Model $record) => $publisher->assign($record, $data['owner_id'] ? (int) $data['owner_id'] : null, $data['reviewer_id'] ? (int) $data['reviewer_id'] : null, keepUnset: true),
                    'assigned',
                    'review',
                )),

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
                ->title('Cannot complete this action')
                ->body(implode(' ', collect($exception->errors())->flatten()->all()))
                ->danger()
                ->persistent()
                ->send();
        } catch (AuthorizationException $exception) {
            Notification::make()->title('Not allowed')->body($exception->getMessage())->danger()->send();
        }
    }

    /**
     * Applies the transition only to records the user may publish; reports skipped ones.
     */
    protected static function runBulk(Collection $records, \Closure $callback, string $verb, string $ability = 'publish'): void
    {
        $done = 0;
        $skipped = 0;
        $failed = [];

        foreach ($records->chunk(100) as $chunk) {
            foreach ($chunk as $record) {
                if (! Gate::allows($ability, $record)) {
                    $skipped++;

                    continue;
                }

                try {
                    $callback($record);
                    $done++;
                } catch (ValidationException $exception) {
                    $failed[] = ($record->title ?? $record->name ?? $record->getKey()).': '.collect($exception->errors())->flatten()->first();
                } catch (AuthorizationException) {
                    $skipped++;
                }
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
