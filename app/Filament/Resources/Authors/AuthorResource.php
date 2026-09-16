<?php

namespace App\Filament\Resources\Authors;

use App\Filament\Resources\Authors\Pages\CreateAuthor;
use App\Filament\Resources\Authors\Pages\EditAuthor;
use App\Filament\Resources\Authors\Pages\ListAuthors;
use App\Filament\Support\MediaFields;
use App\Filament\Support\SeoFields;
use App\Filament\Support\SlugField;
use App\Models\Author;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class AuthorResource extends Resource
{
    protected static ?string $model = Author::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUser;

    protected static string|UnitEnum|null $navigationGroup = 'Insights';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->schema([
                SlugField::source(),
                SlugField::make()->helperText('Public URL: /insights/author/{slug}.'),
                TextInput::make('role_title')->label('Role')->maxLength(120),
                Select::make('user_id')->label('Linked admin user')->relationship('user', 'name')->searchable()->preload()->native(false)->helperText('Optional. Links the author profile to a CMS account.'),
                RichEditor::make('bio')->toolbarButtons(['bold', 'italic', 'link'])->columnSpanFull(),
                MediaFields::image('avatar', 'Photo'),
                KeyValue::make('social_links')->label('Profile links')->keyLabel('Network')->valueLabel('URL')->addActionLabel('Add link'),
                Toggle::make('is_visible')->label('Visible')->default(true)->inline(false),
            ])->columns(2),
            SeoFields::make(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                SpatieMediaLibraryImageColumn::make('avatar')->collection('avatar')->conversion('thumb')->label('')->circular()->size(32),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('role_title')->label('Role')->placeholder('—'),
                TextColumn::make('articles_count')->counts('articles')->label('Articles'),
                IconColumn::make('is_visible')->label('Visible')->boolean(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAuthors::route('/'),
            'create' => CreateAuthor::route('/create'),
            'edit' => EditAuthor::route('/{record}/edit'),
        ];
    }
}
