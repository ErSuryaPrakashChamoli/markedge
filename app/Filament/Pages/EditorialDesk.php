<?php

namespace App\Filament\Pages;

use App\Editorial\RevisionDiff;
use App\Editorial\RevisionManager;
use App\Editorial\WorkflowModels;
use App\Enums\EditorialCommentType;
use App\Filament\Support\PreviewAction;
use App\Filament\Support\PublishActions;
use App\Models\User;
use App\Services\Cms\Publisher;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;

/**
 * One place per record for workflow state, transitions, assignment, internal comments and
 * version history (compare, restore). Reached from every content edit page.
 */
class EditorialDesk extends Page
{
    use WithPagination;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'editorial/{type}/{record}';

    protected string $view = 'filament.pages.editorial-desk';

    public string $type = '';

    public int $recordId = 0;

    public string $commentBody = '';

    private ?Model $resolved = null;

    public function mount(string $type, int $record): void
    {
        $model = WorkflowModels::find($type, $record);

        abort_if($model === null, 404);
        abort_unless(Gate::allows('view', $model), 403);

        $this->type = $type;
        $this->recordId = $record;
    }

    public static function canAccess(): bool
    {
        return auth()->check();
    }

    public function getRecord(): Model
    {
        if ($this->resolved === null) {
            $this->resolved = WorkflowModels::find($this->type, $this->recordId) ?? abort(404);
        }

        return $this->resolved;
    }

    public function getTitle(): string
    {
        return WorkflowModels::titleOf($this->getRecord());
    }

    public function getSubheading(): ?string
    {
        return WorkflowModels::label($this->type).' · editorial desk';
    }

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        $record = $this->getRecord();
        $resource = Filament::getModelResource($record::class);

        $actions = [
            Action::make('edit')->label('Edit content')->icon(Heroicon::OutlinedPencilSquare)->color('gray')
                ->url(fn (): ?string => $resource ? $resource::getUrl('edit', ['record' => $record]) : null)
                ->visible(fn (): bool => $resource !== null && Gate::allows('update', $record)),
            PreviewAction::make()->record($record),
        ];

        foreach (PublishActions::record() as $action) {
            $actions[] = $action->record($record)->after(fn () => $this->resolved = null);
        }

        $actions[] = Action::make('approve')->label('Approve')->icon(Heroicon::OutlinedHandThumbUp)->color('success')
            ->visible(fn (): bool => $record->status?->value === 'review' && ! $record->isApproved() && Gate::allows('review', $record))
            ->requiresConfirmation()->modalDescription('Approval signals the content is ready. Someone with publish rights still has to publish or schedule it.')
            ->action(fn () => $this->attempt(fn () => app(Publisher::class)->approve($record), 'Approved.'));

        $actions[] = Action::make('requestChanges')->label('Request changes')->icon(Heroicon::OutlinedArrowUturnLeft)->color('warning')
            ->visible(fn (): bool => $record->status?->value === 'review' && Gate::allows('review', $record))
            ->schema([Textarea::make('reason')->label('What needs to change?')->required()->rows(4)->maxLength(2000)])
            ->action(fn (array $data) => $this->attempt(fn () => app(Publisher::class)->requestChanges($record, $data['reason']), 'Returned to draft with your note.'));

        $actions[] = Action::make('assign')->label('Assign')->icon(Heroicon::OutlinedUserPlus)->color('gray')
            ->visible(fn (): bool => Gate::allows('review', $record))
            ->fillForm(fn (): array => ['owner_id' => $record->owner_id, 'reviewer_id' => $record->reviewer_id])
            ->schema([
                Select::make('owner_id')->label('Owner')->options(fn () => User::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'))->searchable()->native(false),
                Select::make('reviewer_id')->label('Reviewer')->options(fn () => User::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'))->searchable()->native(false),
            ])
            ->action(fn (array $data) => $this->attempt(fn () => app(Publisher::class)->assign($record, $data['owner_id'] ? (int) $data['owner_id'] : null, $data['reviewer_id'] ? (int) $data['reviewer_id'] : null), 'Assignment saved.'));

        return $actions;
    }

    public function addComment(): void
    {
        $record = $this->getRecord();

        abort_unless(Gate::allows('update', $record) || Gate::allows('review', $record), 403);

        $this->validate(['commentBody' => ['required', 'string', 'max:2000']]);

        $record->editorialComments()->create(['user_id' => auth()->id(), 'type' => EditorialCommentType::Comment, 'body' => trim($this->commentBody)]);
        $this->commentBody = '';

        Notification::make()->title('Comment added.')->success()->send();
    }

    public function resolveComment(int $commentId): void
    {
        $record = $this->getRecord();
        $comment = $record->editorialComments()->whereKey($commentId)->firstOrFail();

        abort_unless(Gate::allows('review', $record) || $comment->user_id === auth()->id(), 403);

        $comment->update(['resolved_at' => now(), 'resolved_by' => auth()->id()]);
    }

    public function compareAction(): Action
    {
        return Action::make('compare')
            ->label('Compare with current')
            ->icon(Heroicon::OutlinedArrowsRightLeft)
            ->color('gray')
            ->modalHeading(fn (array $arguments): string => 'Changes since v'.$arguments['version'])
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Close')
            ->modalContent(function (array $arguments) {
                $record = $this->getRecord();
                $from = $record->revisions()->whereKey($arguments['revision'])->firstOrFail();
                $to = $record->revisions()->orderByDesc('version')->firstOrFail();

                return view('filament.modals.revision-diff', ['from' => $from, 'to' => $to, 'changes' => app(RevisionDiff::class)->between($from, $to)]);
            });
    }

    public function restoreAction(): Action
    {
        return Action::make('restore')
            ->label('Restore')
            ->icon(Heroicon::OutlinedArrowPath)
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading(fn (array $arguments): string => 'Restore v'.$arguments['version'].'?')
            ->modalDescription('A new version is created from this snapshot. If the content is live it returns to Draft so nothing changes publicly until you publish again.')
            ->visible(fn (): bool => Gate::allows('update', $this->getRecord()))
            ->action(function (array $arguments): void {
                $record = $this->getRecord();
                abort_unless(Gate::allows('update', $record), 403);

                $revision = $record->revisions()->whereKey($arguments['revision'])->firstOrFail();

                $this->attempt(function () use ($revision, $record, $arguments): void {
                    $new = app(RevisionManager::class)->restore($revision, $record, (int) $arguments['latest']);
                    Notification::make()->title('Restored as v'.$new->version.'.')->success()->send();
                }, null);

                $this->resolved = null;
            });
    }

    public function rawAction(): Action
    {
        return Action::make('raw')
            ->label('Raw snapshot')
            ->icon(Heroicon::OutlinedCodeBracket)
            ->color('gray')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Close')
            ->visible(fn (): bool => auth()->user()?->isSuperAdmin() ?? false)
            ->modalContent(function (array $arguments) {
                $revision = $this->getRecord()->revisions()->whereKey($arguments['revision'])->firstOrFail();

                return view('filament.modals.revision-raw', ['json' => json_encode($revision->snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
            });
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $record = $this->getRecord()->fresh(['owner', 'reviewer', 'approver', 'creator', 'editor']);
        $this->resolved = $record;

        return [
            'record' => $record,
            'comments' => $record->editorialComments()->with(['user', 'resolver'])->paginate(20, ['*'], 'comments'),
            'revisions' => $record->revisions()->with('author')->paginate(20, ['*'], 'revisions'),
            'latestVersion' => app(RevisionManager::class)->latestVersion($record),
            'changeRequest' => $record->editorialComments()->where('type', EditorialCommentType::ChangeRequest)->unresolved()->first(),
            'canComment' => Gate::allows('update', $record) || Gate::allows('review', $record),
            'canReview' => Gate::allows('review', $record),
            'canRestore' => Gate::allows('update', $record),
            'activity' => Activity::query()->where('subject_type', $record->getMorphClass())->where('subject_id', $record->getKey())->with('causer')->latest('id')->limit(15)->get(),
        ];
    }

    protected function attempt(\Closure $callback, ?string $success): void
    {
        try {
            $callback();

            if ($success !== null) {
                Notification::make()->title($success)->success()->send();
            }
        } catch (ValidationException $exception) {
            Notification::make()->title('Cannot complete this action')->body(implode(' ', collect($exception->errors())->flatten()->all()))->danger()->persistent()->send();
        } catch (AuthorizationException $exception) {
            Notification::make()->title('Not allowed')->body($exception->getMessage())->danger()->send();
        }

        $this->resolved = null;
    }
}
