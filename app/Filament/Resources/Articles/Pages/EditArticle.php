<?php

namespace App\Filament\Resources\Articles\Pages;

use App\Filament\Resources\Articles\ArticleResource;
use App\Filament\Support\PreviewAction;
use App\Filament\Support\PublishActions;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditArticle extends EditRecord
{
    protected static string $resource = ArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [PreviewAction::make(), ...PublishActions::record(), ActionGroup::make([DeleteAction::make(), ForceDeleteAction::make(), RestoreAction::make()])];
    }
}
