<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Filament\Support\MediaFields;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FeaturesRelationManager extends RelationManager
{
    protected static string $relationship = 'features';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->required()->maxLength(120),
            Select::make('product_module_id')->label('Module')->options(fn (): array => $this->getOwnerRecord()->modules()->pluck('name', 'id')->all())->native(false)->placeholder('Not part of a module')->helperText('Features inside a module render under it; others use the group label.'),
            TextInput::make('group_label')->label('Group')->maxLength(60)->helperText('Optional grouping such as Capture, Qualify, Convert.'),
            Textarea::make('description')->rows(3)->maxLength(600)->columnSpanFull(),
            TextInput::make('icon')->maxLength(60)->helperText('Optional Heroicon name.'),
            TextInput::make('sort_order')->numeric()->default(0),
            MediaFields::image('image', 'Illustration')->columnSpanFull(),
            Repeater::make('capabilities')->relationship()->orderColumn('sort_order')->defaultItems(0)->schema([
                TextInput::make('name')->required()->maxLength(160),
                Textarea::make('description')->rows(2)->maxLength(600),
            ])->collapsible()->collapsed()->itemLabel(fn (array $state): ?string => $state['name'] ?? null)->maxItems(20)->columnSpanFull()
                ->helperText('Capabilities are the finest grain of the product hierarchy: Product → Module → Feature → Capability.'),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->defaultGroup('group_label')
            ->columns([
                TextColumn::make('title')->searchable(),
                TextColumn::make('module.name')->label('Module')->badge()->color('primary')->placeholder('—'),
                TextColumn::make('group_label')->label('Group')->badge()->color('gray')->placeholder('—'),
                TextColumn::make('capabilities_count')->counts('capabilities')->label('Capabilities'),
                TextColumn::make('description')->limit(60)->placeholder('—'),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
