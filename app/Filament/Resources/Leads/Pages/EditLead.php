<?php

namespace App\Filament\Resources\Leads\Pages;

use App\Enums\LeadStatus;
use App\Filament\Resources\Leads\LeadResource;
use App\Models\User;
use App\Sales\LeadWorkflow;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

/**
 * Stage, owner and qualification changes made through the edit form pass through LeadWorkflow so
 * the timeline, timestamps and notifications match the dedicated actions.
 */
class EditLead extends EditRecord
{
    protected static string $resource = LeadResource::class;

    protected function getHeaderActions(): array
    {
        return [ViewAction::make(), DeleteAction::make()];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $workflow = app(LeadWorkflow::class);
        $actor = auth()->user();
        $status = LeadStatus::tryFrom((string) ($data['status'] ?? '')) ?? $record->status;
        $reason = $data['lost_reason'] ?? null;
        $ownerId = isset($data['assigned_to']) ? (int) $data['assigned_to'] : null;
        $qualification = $data['qualification'] ?? null;

        unset($data['status'], $data['lost_reason'], $data['assigned_to'], $data['qualification']);

        try {
            if ($status !== $record->status) {
                $workflow->transition($record, $status, $actor, $reason);
            }

            if ($ownerId !== ($record->assigned_to ? (int) $record->assigned_to : null)) {
                $workflow->assign($record, $ownerId ? User::query()->find($ownerId) : null, $actor, $data['team'] ?? null);
            }

            $workflow->qualify($record, $actor, is_array($qualification) ? $qualification : null);
        } catch (ValidationException $exception) {
            throw ValidationException::withMessages(collect($exception->errors())->mapWithKeys(fn (array $messages, string $key) => ["data.{$key}" => $messages])->all());
        }

        $record->refresh()->update($data);

        return $record->refresh();
    }
}
