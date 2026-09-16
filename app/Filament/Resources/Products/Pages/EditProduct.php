<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Filament\Support\ContentHealthAction;
use App\Filament\Support\EditorialDeskAction;
use App\Filament\Support\PreviewAction;
use App\Filament\Support\SeoDiagnosticsAction;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [PreviewAction::make(), SeoDiagnosticsAction::make(), EditorialDeskAction::make(), ContentHealthAction::make(), ActionGroup::make([DeleteAction::make(), ForceDeleteAction::make(), RestoreAction::make()])];
    }
}
