<?php

namespace App\Filament\Resources\SocialLinks;

use App\Cms\Blocks\Fields;
use App\Filament\Resources\SocialLinks\Pages\ManageSocialLinks;
use App\Models\SocialLink;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class SocialLinkResource extends Resource
{
    protected static ?string $model = SocialLink::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static string|UnitEnum|null $navigationGroup = 'Website';

    protected static ?int $navigationSort = 4;

    /** @var array<string, string> */
    public const array PLATFORMS = [
        'linkedin' => 'LinkedIn', 'x' => 'X (Twitter)', 'facebook' => 'Facebook', 'instagram' => 'Instagram',
        'youtube' => 'YouTube', 'github' => 'GitHub', 'whatsapp' => 'WhatsApp', 'other' => 'Other',
    ];

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('platform')->options(self::PLATFORMS)->required()->native(false)->live()
                ->helperText('Not listed? Choose Other, name it and upload its icon below.'),
            TextInput::make('label')->maxLength(40)->required(fn (Get $get): bool => $get('platform') === 'other')
                ->helperText('Defaults to the platform name. Required for Other, e.g. "TikTok".'),
            TextInput::make('url')->label('Profile URL')->url()->required()->maxLength(500)->columnSpanFull(),
            Fields::image('icon', 'Custom icon')
                ->directory('social-icons')
                ->acceptedFileTypes(['image/png', 'image/webp'])
                ->maxSize(1024)
                ->imageEditor(false)
                ->columnSpanFull()
                ->helperText('Needed for Other; optional for listed platforms, where it replaces the built-in icon. Square PNG or WebP with a transparent background, up to 1 MB. Shown as a single-colour silhouette to match the other icons.'),
            Toggle::make('is_visible')->label('Visible')->default(true),
            TextInput::make('sort_order')->numeric()->default(0),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                ImageColumn::make('icon')->disk('public')->imageSize(24)->placeholder('Built-in'),
                TextColumn::make('platform')->badge()->formatStateUsing(fn (string $state): string => self::PLATFORMS[$state] ?? $state),
                TextColumn::make('label')->placeholder('—'),
                TextColumn::make('url')->limit(50),
                IconColumn::make('is_visible')->label('Visible')->boolean(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageSocialLinks::route('/')];
    }
}
