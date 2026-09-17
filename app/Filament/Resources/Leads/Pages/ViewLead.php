<?php

namespace App\Filament\Resources\Leads\Pages;

use App\Enums\FollowUpType;
use App\Enums\LeadStatus;
use App\Filament\Resources\Leads\LeadResource;
use App\Models\LeadFollowUp;
use App\Models\User;
use App\Sales\LeadWorkflow;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * The sales working view: stage moves, ownership, notes and follow-ups as explicit actions.
 */
class ViewLead extends ViewRecord
{
    protected static string $resource = LeadResource::class;

    protected function getHeaderActions(): array
    {
        $canUpdate = fn (): bool => Gate::allows('update', $this->getRecord());
        $activeUsers = fn () => User::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id');

        return [
            Action::make('moveStage')->label('Move stage')->icon(Heroicon::OutlinedArrowRightCircle)->color('primary')
                ->visible($canUpdate)
                ->schema([
                    Select::make('status')->label('Stage')->required()->native(false)->live()
                        ->options(fn (): array => collect(app(LeadWorkflow::class)->optionsFor($this->getRecord()))->except($this->getRecord()->status->value)->all()),
                    Select::make('lost_reason')->label('Reason')->options(config('markedge.sales.lost_reasons', []))->native(false)
                        ->visible(fn (callable $get): bool => in_array($get('status'), [LeadStatus::Lost->value, LeadStatus::Unqualified->value], true))
                        ->requiredIf('status', [LeadStatus::Lost->value, LeadStatus::Unqualified->value]),
                ])
                ->action(function (array $data): void {
                    $this->run(fn (LeadWorkflow $workflow) => $workflow->transition($this->getRecord(), LeadStatus::from($data['status']), auth()->user(), $data['lost_reason'] ?? null), 'Stage updated.');
                }),
            Action::make('assignOwner')->label('Assign')->icon(Heroicon::OutlinedUserPlus)
                ->visible($canUpdate)
                ->schema(array_filter([
                    Select::make('assigned_to')->label('Owner')->options($activeUsers)->default(fn (): ?int => $this->getRecord()->assigned_to)->searchable()->native(false),
                    config('markedge.sales.teams', []) !== [] ? Select::make('team')->options(array_combine(config('markedge.sales.teams'), config('markedge.sales.teams')))->default(fn (): ?string => $this->getRecord()->team)->native(false) : null,
                ]))
                ->action(function (array $data): void {
                    $owner = isset($data['assigned_to']) ? User::query()->find($data['assigned_to']) : null;
                    $this->run(fn (LeadWorkflow $workflow) => $workflow->assign($this->getRecord(), $owner, auth()->user(), $data['team'] ?? null), 'Owner updated.');
                }),
            Action::make('addNote')->label('Add note')->icon(Heroicon::OutlinedPencilSquare)
                ->visible($canUpdate)
                ->schema([Textarea::make('body')->label('Note')->required()->rows(4)->maxLength(5000)])
                ->action(function (array $data): void {
                    $this->run(fn (LeadWorkflow $workflow) => $workflow->addNote($this->getRecord(), auth()->user(), (string) $data['body']), 'Note added.');
                }),
            Action::make('scheduleFollowUp')->label('Schedule follow-up')->icon(Heroicon::OutlinedCalendarDays)
                ->visible($canUpdate)
                ->schema([
                    Select::make('type')->options(FollowUpType::class)->default(FollowUpType::Call->value)->required()->native(false),
                    DateTimePicker::make('due_at')->label('Due')->required()->native(false)->seconds(false)->default(fn () => now()->addDay()->setTime(10, 0)),
                    Select::make('user_id')->label('Owner')->options($activeUsers)->default(fn (): ?int => $this->getRecord()->assigned_to ?? auth()->id())->required()->native(false),
                    Textarea::make('note')->rows(3)->maxLength(2000),
                ])
                ->action(function (array $data): void {
                    $this->run(fn (LeadWorkflow $workflow) => $workflow->scheduleFollowUp($this->getRecord(), auth()->user(), $data), 'Follow-up scheduled.');
                }),
            Action::make('completeFollowUp')->label('Complete follow-up')->icon(Heroicon::OutlinedCheckCircle)->color('success')
                ->visible(fn (): bool => $canUpdate() && $this->getRecord()->followUps()->open()->exists())
                ->schema([
                    Select::make('follow_up_id')->label('Follow-up')->required()->native(false)
                        ->options(fn (): array => $this->getRecord()->followUps()->open()->get()->mapWithKeys(fn (LeadFollowUp $f) => [$f->id => $f->type->getLabel().' · due '.$f->due_at->format('d M Y H:i')])->all()),
                    Textarea::make('outcome')->rows(3)->maxLength(2000),
                ])
                ->action(function (array $data): void {
                    $followUp = $this->getRecord()->followUps()->open()->findOrFail($data['follow_up_id']);
                    $this->run(fn (LeadWorkflow $workflow) => $workflow->completeFollowUp($followUp, auth()->user(), $data['outcome'] ?? null), 'Follow-up completed.');
                }),
            EditAction::make(),
        ];
    }

    protected function run(callable $callback, string $success): void
    {
        try {
            $callback(app(LeadWorkflow::class));
            $this->getRecord()->refresh();
            Notification::make()->title($success)->success()->send();
        } catch (ValidationException $exception) {
            Notification::make()->title('Cannot complete this action')->body(implode(' ', collect($exception->errors())->flatten()->all()))->danger()->persistent()->send();
        }
    }
}
