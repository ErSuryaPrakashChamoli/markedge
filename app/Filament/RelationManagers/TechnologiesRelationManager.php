<?php

namespace App\Filament\RelationManagers;

use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Technology stack attached through the polymorphic technologyables pivot.
 */
class TechnologiesRelationManager extends RelationManager
{
    protected static string $relationship = 'technologies';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('category')->badge(),
            ])
            ->headerActions([
                AttachAction::make()->preloadRecordSelect()->multiple()->recordSelectSearchColumns(['name']),
            ])
            ->recordActions([DetachAction::make()])
            ->toolbarActions([BulkActionGroup::make([DetachBulkAction::make()])]);
    }
}
