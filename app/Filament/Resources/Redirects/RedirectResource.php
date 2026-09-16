<?php

namespace App\Filament\Resources\Redirects;

use App\Enums\RedirectStatus;
use App\Filament\Resources\Redirects\Pages\ManageRedirects;
use App\Models\Redirect;
use App\Rules\SafeRedirectDestination;
use BackedEnum;
use Closure;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class RedirectResource extends Resource
{
    protected static ?string $model = Redirect::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static string|UnitEnum|null $navigationGroup = 'SEO';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'from_path';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('from_path')
                ->label('Old path')
                ->required()
                ->maxLength(500)
                ->placeholder('/old-page')
                ->helperText('Site path only. Normalised to lowercase without a trailing slash.')
                ->rules(['regex:#^/#'])
                ->unique(ignoreRecord: true, modifyRuleUsing: fn ($rule, Get $get) => $rule->where('from_path', Redirect::normalisePath((string) $get('from_path')))),
            TextInput::make('to_url')
                ->label('Destination')
                ->required()
                ->maxLength(500)
                ->placeholder('/new-page')
                ->helperText('A site path. External https URLs are accepted only for hosts on the configured allow-list.')
                ->rules([
                    new SafeRedirectDestination,
                    fn (Get $get, ?Model $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get, $record): void {
                        $from = Redirect::normalisePath((string) $get('from_path'));

                        if (str_starts_with((string) $value, '/') && Redirect::normalisePath((string) $value) === $from) {
                            $fail('A redirect cannot point to itself.');

                            return;
                        }

                        if (Redirect::createsLoop($from, (string) $value, $record?->getKey())) {
                            $fail('This destination redirects back to the old path and would create a loop.');
                        }
                    },
                ]),
            Select::make('status_code')->label('HTTP status')->options(RedirectStatus::class)->default(RedirectStatus::MovedPermanently)->required()->native(false),
            Toggle::make('is_active')->label('Active')->default(true)->inline(false),
            Textarea::make('notes')->rows(2)->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('from_path')->label('Old path')->searchable()->copyable(),
                TextColumn::make('to_url')->label('Destination')->searchable()->limit(50),
                TextColumn::make('status_code')->label('Status')->badge()->color('gray'),
                IconColumn::make('is_active')->label('Active')->boolean(),
                TextColumn::make('hit_count')->label('Hits')->sortable(),
                TextColumn::make('last_hit_at')->label('Last hit')->since()->placeholder('—'),
            ])
            ->filters([TernaryFilter::make('is_active')->label('Active')])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageRedirects::route('/')];
    }
}
