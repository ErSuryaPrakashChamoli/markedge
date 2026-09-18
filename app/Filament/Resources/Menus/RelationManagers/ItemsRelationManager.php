<?php

namespace App\Filament\Resources\Menus\RelationManagers;

use App\Enums\MenuItemType;
use App\Models\ArticleCategory;
use App\Models\Industry;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Product;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Solution;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\MorphToSelect;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Menu items with three levels of nesting, entity links and automatic children.
 * Unpublished targets are filtered by the frontend MenuBuilder, never here.
 */
class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Menu items';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->schema([
                TextInput::make('label')->required()->maxLength(80),
                Select::make('parent_id')
                    ->label('Parent item')
                    ->options(fn (?MenuItem $record): array => $this->getOwnerRecord()->items()
                        ->when($record, fn (Builder $query) => $query->whereKeyNot($record->getKey()))
                        ->orderBy('sort_order')
                        ->pluck('label', 'id')
                        ->all())
                    ->searchable()
                    ->native(false)
                    ->helperText('Up to three levels: top item → group → link.'),
                Select::make('type')
                    ->options(MenuItemType::class)
                    ->default(MenuItemType::Url)
                    ->required()
                    ->native(false)
                    ->live()
                    ->helperText('Footer column titles and header groups become clickable when given a URL or site content; a heading is a plain label.'),
                TextInput::make('url')
                    ->label('URL')
                    ->maxLength(500)
                    ->rules(['nullable', 'regex:#^(/[^\s]*|https?://[^\s]+|mailto:[^\s]+|tel:[^\s]+)$#'])
                    ->helperText('A site path such as /services or a full https:// URL.')
                    ->visible(fn (Get $get): bool => $get('type') === MenuItemType::Url->value || $get('type') === MenuItemType::Url)
                    ->required(fn (Get $get): bool => $get('type') === MenuItemType::Url->value || $get('type') === MenuItemType::Url),
                MorphToSelect::make('linkable')
                    ->label('Link to')
                    ->types([
                        MorphToSelect\Type::make(Page::class)->titleAttribute('title'),
                        MorphToSelect\Type::make(ServiceCategory::class)->titleAttribute('name'),
                        MorphToSelect\Type::make(Service::class)->titleAttribute('name'),
                        MorphToSelect\Type::make(Product::class)->titleAttribute('name'),
                        MorphToSelect\Type::make(Solution::class)->titleAttribute('name'),
                        MorphToSelect\Type::make(Industry::class)->titleAttribute('name'),
                        MorphToSelect\Type::make(ArticleCategory::class)->titleAttribute('name'),
                    ])
                    ->visible(fn (Get $get): bool => $get('type') === MenuItemType::Entity->value || $get('type') === MenuItemType::Entity)
                    ->columnSpanFull(),
            ])->columns(2),
            Section::make('Display')->schema([
                TextInput::make('description')->maxLength(120)->helperText('Shown under the label in mega menus.'),
                TextInput::make('badge')->maxLength(20),
                Select::make('settings.auto_children')
                    ->label('Automatic children')
                    ->options([
                        'service_category' => 'Services of the linked category',
                        'products' => 'All visible products',
                        'solutions' => 'All published solutions',
                        'industries' => 'All published industries',
                        'article_categories' => 'All article categories',
                    ])
                    ->native(false)
                    ->helperText('Appended automatically so new content appears without editing the menu.'),
                Toggle::make('settings.mega_menu')->label('Render as mega menu (top-level items only)'),
                Toggle::make('open_in_new_tab')->label('Open in new tab'),
                Toggle::make('is_visible')->label('Visible')->default(true),
                TextInput::make('sort_order')->numeric()->default(0),
            ])->columns(2)->collapsible(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['parent', 'linkable']))
            ->recordTitleAttribute('label')
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->defaultGroup('parent.label')
            ->columns([
                TextColumn::make('label')->searchable(),
                TextColumn::make('type')->badge(),
                TextColumn::make('url')->limit(40)->placeholder('—'),
                TextColumn::make('linkable_type')->label('Linked')->badge()->color('gray')->placeholder('—'),
                IconColumn::make('is_visible')->label('Visible')->boolean(),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
