<?php

namespace App\Cms\Blocks\Types;

use App\Cms\Blocks\Block;
use App\Cms\Blocks\Fields;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;

class FeatureGrid extends Block
{
    public function key(): string
    {
        return 'feature_grid';
    }

    public function label(): string
    {
        return 'Feature grid';
    }

    public function icon(): Heroicon
    {
        return Heroicon::OutlinedSquares2x2;
    }

    public function fields(): array
    {
        return [
            Fields::heading(required: true),
            Fields::intro(),
            Repeater::make('items')->schema([
                TextInput::make('title')->required()->maxLength(120),
                Textarea::make('text')->rows(2)->maxLength(400),
                TextInput::make('icon')->helperText('Optional Heroicon name, e.g. heroicon-o-bolt.')->maxLength(60),
            ])->minItems(1)->maxItems(12)->collapsible()->itemLabel(fn (array $state): ?string => $state['title'] ?? null)->columnSpanFull(),
            Select::make('columns')->options([2 => '2 columns', 3 => '3 columns', 4 => '4 columns'])->default(3)->native(false),
        ];
    }

    public function rules(): array
    {
        return [
            'heading' => ['required', 'string', 'max:255'],
            'intro' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1', 'max:12'],
            'items.*.title' => ['required', 'string', 'max:120'],
            'items.*.text' => ['nullable', 'string', 'max:400'],
            'items.*.icon' => ['nullable', 'string', 'max:60', 'regex:/^heroicon-[a-z]-[a-z0-9-]+$/'],
            'columns' => ['nullable', 'in:2,3,4'],
        ];
    }
}
