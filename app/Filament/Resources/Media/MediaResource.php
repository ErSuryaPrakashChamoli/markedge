<?php

namespace App\Filament\Resources\Media;

use App\Editorial\MediaUsage;
use App\Filament\Resources\Media\Pages\EditMedia;
use App\Filament\Resources\Media\Pages\ListMedia;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Number;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use UnitEnum;

/**
 * Browse every uploaded file and manage alt text, captions and descriptions.
 * Uploads happen on the record that owns the media (architecture §24).
 */
class MediaResource extends Resource
{
    protected static ?string $model = Media::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static string|UnitEnum|null $navigationGroup = 'Media';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Media Library';

    protected static ?string $modelLabel = 'media item';

    protected static ?string $pluralModelLabel = 'media library';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Accessibility and captions')->schema([
                TextInput::make('name')->required()->maxLength(255),
                TextInput::make('custom_properties.alt')->label('Alt text')->maxLength(255)->helperText('Describe the image for screen readers and search engines. Required for images used on the website.'),
                TextInput::make('custom_properties.caption')->label('Caption')->maxLength(255),
                Textarea::make('custom_properties.description')->label('Description')->rows(2)->maxLength(600)->columnSpanFull(),
            ])->columns(2),
            Section::make('File')->schema([
                Placeholder::make('preview')->label('')->content(fn (Media $record) => str_starts_with($record->mime_type, 'image/')
                    ? new HtmlString('<img src="'.e($record->getUrl()).'" alt="" class="max-h-64 rounded">')
                    : $record->file_name)->columnSpanFull(),
                Placeholder::make('file_name')->label('File name')->content(fn (Media $record): string => $record->file_name),
                Placeholder::make('mime_type')->label('Type')->content(fn (Media $record): string => $record->mime_type),
                Placeholder::make('size')->label('Size')->content(fn (Media $record): string => Number::fileSize($record->size)),
                Placeholder::make('dimensions')->label('Dimensions')->content(fn (Media $record): string => static::dimensions($record)),
                Placeholder::make('owner')->label('Used by')->content(fn (Media $record): string => static::ownerLabel($record)),
                Placeholder::make('collection_name')->label('Collection')->content(fn (Media $record): string => $record->collection_name),
                Placeholder::make('conversions')->label('Conversions')->content(fn (Media $record): string => implode(', ', array_keys(array_filter($record->generated_conversions ?? []))) ?: 'None yet'),
                Placeholder::make('url')->label('URL')->content(fn (Media $record): string => $record->getUrl()),
            ])->columns(3)->collapsible(),
        ]);
    }

    public static function dimensions(Media $record): string
    {
        $width = $record->getCustomProperty('width');
        $height = $record->getCustomProperty('height');

        return $width && $height ? "{$width} × {$height}" : '—';
    }

    public static function ownerLabel(Media $record): string
    {
        $owner = $record->model;

        if ($owner === null) {
            return $record->model_type.' #'.$record->model_id.' (missing)';
        }

        return ucfirst(str_replace('_', ' ', $record->model_type)).': '.($owner->title ?? $owner->name ?? $owner->key ?? '#'.$owner->getKey());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('model'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                ImageColumn::make('preview')->label('')->state(fn (Media $record): ?string => str_starts_with($record->mime_type, 'image/') ? $record->getUrl(static::previewConversion($record)) : null)->square()->size(48),
                TextColumn::make('name')->searchable()->sortable()->description(fn (Media $record): string => $record->file_name),
                TextColumn::make('custom_properties.alt')->label('Alt text')->placeholder('Missing')->limit(40),
                TextColumn::make('model_type')->label('Used by')->state(fn (Media $record): string => MediaUsage::label($record))->badge()->color(fn (Media $record): string => $record->model === null ? 'danger' : 'gray')->wrap(),
                TextColumn::make('collection_name')->label('Collection')->badge()->color('gray'),
                TextColumn::make('mime_type')->label('Type')->toggleable(),
                TextColumn::make('size')->formatStateUsing(fn (int $state): string => Number::fileSize($state))->sortable(),
                TextColumn::make('created_at')->label('Uploaded')->dateTime('d M Y')->sortable(),
            ])
            ->filters([
                SelectFilter::make('mime_type')->label('Type')->options(['image/jpeg' => 'JPEG', 'image/png' => 'PNG', 'image/webp' => 'WebP', 'application/pdf' => 'PDF']),
                SelectFilter::make('model_type')->label('Used by')->options(fn (): array => Media::query()->distinct()->orderBy('model_type')->pluck('model_type', 'model_type')->map(fn (string $type): string => ucfirst(str_replace('_', ' ', $type)))->all()),
                SelectFilter::make('collection_name')->label('Collection')->options(fn (): array => Media::query()->distinct()->orderBy('collection_name')->pluck('collection_name', 'collection_name')->all()),
                SelectFilter::make('usage')->label('Usage')->options(['orphaned' => 'Unused (owner missing)'])->query(fn (Builder $query, array $data): Builder => $query->when($data['value'] === 'orphaned', fn (Builder $q) => MediaUsage::orphaned($q))),
                SelectFilter::make('missing_alt')->label('Alt text')->options(['missing' => 'Missing alt text'])->query(fn (Builder $query, array $data): Builder => $query->when($data['value'] === 'missing', fn (Builder $q) => $q->where('mime_type', 'like', 'image/%')->where(fn (Builder $inner) => $inner->whereNull('custom_properties->alt')->orWhere('custom_properties->alt', '')))),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    protected static function previewConversion(Media $record): string
    {
        return $record->hasGeneratedConversion('thumb') ? 'thumb' : '';
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMedia::route('/'),
            'edit' => EditMedia::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
