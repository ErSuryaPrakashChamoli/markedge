<?php

namespace App\Filament\Resources\Testimonials;

use App\Filament\Resources\Testimonials\Pages\CreateTestimonial;
use App\Filament\Resources\Testimonials\Pages\EditTestimonial;
use App\Filament\Resources\Testimonials\Pages\ListTestimonials;
use App\Filament\Support\MediaFields;
use App\Models\Testimonial;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class TestimonialResource extends Resource
{
    protected static ?string $model = Testimonial::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|UnitEnum|null $navigationGroup = 'Work';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'author_name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->description('Record only testimonials that were genuinely given. Ratings are not collected.')->schema([
                TextInput::make('author_name')->label('Person')->required()->maxLength(120),
                TextInput::make('author_role')->label('Designation')->maxLength(120),
                Select::make('client_id')->label('Client')->relationship('client', 'name')->searchable()->preload()->native(false),
                TextInput::make('company_name')->maxLength(120)->helperText('Used when the client is not listed.'),
                Textarea::make('quote')->required()->rows(4)->maxLength(1200)->columnSpanFull(),
                Select::make('product_id')->label('About product')->relationship('product', 'name')->searchable()->preload()->native(false),
                Select::make('service_id')->label('About service')->relationship('service', 'name')->searchable()->preload()->native(false),
                MediaFields::image('avatar', 'Photo'),
                DatePicker::make('given_at')->label('Date given')->native(false),
                Toggle::make('is_visible')->label('Show on the website')->inline(false),
                TextInput::make('sort_order')->numeric()->default(0),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('client'))
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('author_name')->label('Person')->searchable()->description(fn (Testimonial $record): ?string => $record->author_role),
                TextColumn::make('company_name')->label('Company')->placeholder(fn (Testimonial $record): string => $record->client?->name ?? '—'),
                TextColumn::make('quote')->limit(60),
                IconColumn::make('is_visible')->label('Visible')->boolean(),
            ])
            ->filters([TernaryFilter::make('is_visible')->label('Visible'), TrashedFilter::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make(), RestoreBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTestimonials::route('/'),
            'create' => CreateTestimonial::route('/create'),
            'edit' => EditTestimonial::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
