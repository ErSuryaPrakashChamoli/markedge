<?php

namespace App\Filament\Resources\Ctas\Pages;

use App\Filament\Resources\Ctas\CtaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCtas extends ManageRecords
{
    protected static string $resource = CtaResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
