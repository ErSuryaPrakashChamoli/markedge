<?php

namespace App\Filament\Resources\GrievanceLevels\Pages;

use App\Filament\Resources\GrievanceLevels\GrievanceLevelResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageGrievanceLevels extends ManageRecords
{
    protected static string $resource = GrievanceLevelResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
