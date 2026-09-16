<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Filament\Support\MediaFields;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
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
            TextInput::make('group_label')->label('Group')->maxLength(60)->helperText('Optional grouping such as Capture, Qualify, Convert.'),
            Textarea::make('description')->rows(3)->maxLength(600)->columnSpanFull(),
            TextInput::make('icon')->maxLength(60)->helperText('Optional Heroicon name.'),
            TextInput::make('sort_order')->numeric()->default(0),
            MediaFields::image('image', 'Illustration')->columnSpanFull(),
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
                TextColumn::make('group_label')->label('Group')->badge()->color('gray')->placeholder('—'),
                TextColumn::make('description')->limit(60)->placeholder('—'),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
