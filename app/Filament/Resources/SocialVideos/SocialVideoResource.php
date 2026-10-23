<?php

namespace App\Filament\Resources\SocialVideos;

use App\Enums\SocialPlatform;
use App\Filament\Resources\SocialVideos\Pages\CreateSocialVideo;
use App\Filament\Resources\SocialVideos\Pages\EditSocialVideo;
use App\Filament\Resources\SocialVideos\Pages\ListSocialVideos;
use App\Filament\Support\Columns;
use App\Filament\Support\MediaFields;
use App\Models\SocialVideo;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class SocialVideoResource extends Resource
{
    protected static ?string $model = SocialVideo::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedVideoCamera;

    protected static string|UnitEnum|null $navigationGroup = 'Resources';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Post')->description('Paste the public link of a video you posted on Instagram, LinkedIn, YouTube, Facebook, TikTok or any other platform.')->schema([
                TextInput::make('url')->label('Post link')->required()->url()->maxLength(500)->columnSpanFull()
                    ->rules(['starts_with:https://'])
                    ->placeholder('https://www.instagram.com/reel/…')
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Set $set, ?string $state): void {
                        $platform = SocialPlatform::fromUrl($state);

                        if ($platform !== null) {
                            $set('platform', $platform->value);
                            $set('is_vertical', $platform->prefersVertical());
                        }
                    }),
                Select::make('platform')->options(SocialPlatform::class)->required()->native(false)->live()
                    ->helperText('Filled in from the link; change it if the guess is wrong.'),
                Placeholder::make('playback')->label('On the website')
                    ->content(fn (Get $get): string => static::playbackHint($get('platform'), $get('url'))),
                TextInput::make('title')->required()->maxLength(255)->columnSpanFull(),
                Textarea::make('caption')->label('Short description')->rows(3)->maxLength(500)->columnSpanFull(),
            ])->columns(2),
            Section::make('Display')->schema([
                MediaFields::image('featured', 'Cover image', 'Shown before the video plays. A frame from the video works best; portrait 4:5 or 9:16 for reels. JPG, PNG or WebP up to 5 MB.')->columnSpanFull(),
                DatePicker::make('posted_at')->label('Posted on')->native(false)->maxDate(now()),
                TextInput::make('sort_order')->numeric()->default(0),
                Toggle::make('is_vertical')->label('Vertical video (reel / short)')->inline(false),
                Toggle::make('is_featured')->label('Featured')->helperText('Featured videos are listed first.')->inline(false),
                Toggle::make('is_visible')->label('Show on the website')->default(true)->inline(false),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('media'))
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                SpatieMediaLibraryImageColumn::make('featured')->collection('featured')->conversion('thumb')->label('')->size(48),
                TextColumn::make('title')->searchable()->limit(60)->description(fn (SocialVideo $record): ?string => $record->caption ? str($record->caption)->limit(70)->toString() : null),
                TextColumn::make('platform')->badge()->color('gray')->sortable(),
                TextColumn::make('posted_at')->label('Posted')->date('d M Y')->sortable()->placeholder('—'),
                Columns::featured(),
                IconColumn::make('is_visible')->label('Visible')->boolean(),
                Columns::updatedAt(),
            ])
            ->filters([
                SelectFilter::make('platform')->options(SocialPlatform::class)->multiple(),
                TernaryFilter::make('is_visible')->label('Visible'),
                TernaryFilter::make('is_featured')->label('Featured'),
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('open')->label('Open post')->icon(Heroicon::OutlinedArrowTopRightOnSquare)->color('gray')
                    ->url(fn (SocialVideo $record): string => $record->url)->openUrlInNewTab(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make(), RestoreBulkAction::make()])]);
    }

    /**
     * Tells the editor up front whether the link will play on the site or open the platform.
     */
    public static function playbackHint(SocialPlatform|string|null $platform, ?string $url): string
    {
        $platform = is_string($platform) ? SocialPlatform::tryFrom($platform) : $platform;

        if ($platform === null || blank($url)) {
            return 'Paste a link to check.';
        }

        return $platform->embedUrl($url) !== null
            ? 'Plays right on the page in the '.$platform->getLabel().' player.'
            : 'Opens the post on '.$platform->getLabel().' in a new tab (this link cannot be played on the page).';
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSocialVideos::route('/'),
            'create' => CreateSocialVideo::route('/create'),
            'edit' => EditSocialVideo::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
