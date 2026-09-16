<?php

namespace App\Filament\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * FAQs owned by any entity (polymorphic). Reused by every resource with HasFaqs.
 */
class FaqsRelationManager extends RelationManager
{
    protected static string $relationship = 'faqs';

    protected static ?string $title = 'FAQs';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('question')->required()->maxLength(255)->columnSpanFull(),
            RichEditor::make('answer')->required()->toolbarButtons(['bold', 'italic', 'bulletList', 'orderedList', 'link'])->columnSpanFull(),
            Toggle::make('is_visible')->label('Visible')->default(true),
            TextInput::make('sort_order')->numeric()->default(0),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('question')
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('question')->searchable()->wrap(),
                IconColumn::make('is_visible')->label('Visible')->boolean(),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
