<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Enums\PublishStatus;
use App\Filament\Support\PreviewAction;
use App\Filament\Support\SlugField;
use App\Models\Product;
use App\Models\ProductDocument;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;

/**
 * Product documentation pages. Publishing needs products.publish; drafts are never public.
 */
class DocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    protected static ?string $title = 'Documentation';

    public function form(Schema $schema): Schema
    {
        $canPublish = fn (): bool => Gate::allows('publish', $this->getOwnerRecord());

        return $schema->components([
            TextInput::make('title')->required()->maxLength(160),
            SlugField::make()->helperText(fn (): string => 'Public URL: /products/'.$this->getOwnerRecord()->slug.'/docs/{slug}.'),
            TextInput::make('section')->maxLength(80)->helperText('Groups pages on the documentation index, e.g. Getting started.'),
            TextInput::make('sort_order')->numeric()->default(0),
            Textarea::make('excerpt')->rows(2)->maxLength(300)->columnSpanFull(),
            RichEditor::make('body')->columnSpanFull(),
            Select::make('status')
                ->options(fn (): array => collect(PublishStatus::cases())->filter(fn (PublishStatus $s) => $canPublish() || $s === PublishStatus::Draft)->mapWithKeys(fn (PublishStatus $s) => [$s->value => $s->getLabel()])->all())
                ->default(PublishStatus::Draft->value)->required()->native(false)
                ->helperText('Published pages are public only while the product itself is visible.'),
            DateTimePicker::make('published_at')->label('Publish date')->native(false)->seconds(false)->visible($canPublish),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('title')->searchable()->description(fn (ProductDocument $record): string => '/products/'.$this->getOwnerRecord()->slug.'/docs/'.$record->slug),
                TextColumn::make('section')->badge()->color('gray')->placeholder('—'),
                TextColumn::make('status')->badge(),
                TextColumn::make('published_at')->dateTime('d M Y H:i')->placeholder('—'),
            ])
            ->filters([SelectFilter::make('status')->options(PublishStatus::class)])
            ->headerActions([CreateAction::make()->mutateDataUsing(fn (array $data): array => $this->guardStatus($data))])
            ->recordActions([EditAction::make()->mutateDataUsing(fn (array $data): array => $this->guardStatus($data)), PreviewAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    /**
     * Without products.publish only drafts can be saved, whatever the request contains.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function guardStatus(array $data): array
    {
        /** @var Product $product */
        $product = $this->getOwnerRecord();

        if (! Gate::allows('publish', $product)) {
            $data['status'] = PublishStatus::Draft->value;
            unset($data['published_at']);
        } elseif (($data['status'] ?? null) === PublishStatus::Published->value && blank($data['published_at'] ?? null)) {
            $data['published_at'] = now();
        }

        return $data;
    }
}
