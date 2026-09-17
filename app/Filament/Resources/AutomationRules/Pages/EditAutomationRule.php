<?php

namespace App\Filament\Resources\AutomationRules\Pages;

use App\Filament\Resources\AutomationRules\AutomationRuleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAutomationRule extends EditRecord
{
    protected static string $resource = AutomationRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return AutomationRuleResource::normalise($data);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['conditions'] = array_map(function (array $c): array {
            $c['value'] = is_array($c['value'] ?? null) ? implode(', ', $c['value']) : ($c['value'] ?? null);

            return $c;
        }, $data['conditions'] ?? []);

        return $data;
    }
}
