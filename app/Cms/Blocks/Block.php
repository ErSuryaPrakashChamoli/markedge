<?php

namespace App\Cms\Blocks;

use BackedEnum;
use Filament\Forms\Components\Builder\Block as FilamentBlock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Support\Icons\Heroicon;

/**
 * A typed, whitelisted page block. Admins fill fixed fields; code owns the rendering.
 * Every block carries the same display settings (enabled, theme, anchor) and nothing else
 * that could alter the design system (architecture §6.2).
 */
abstract class Block
{
    /** @var array<int, string> */
    public const array HOSTS = ['page', 'landing_page', 'product', 'service', 'service_category', 'solution', 'industry', 'case_study'];

    /** @var array<int, string> */
    public const array THEMES = ['light', 'dark', 'neutral'];

    abstract public function key(): string;

    abstract public function label(): string;

    /**
     * Filament components for this block's own fields.
     *
     * @return array<int, Component>
     */
    abstract public function fields(): array;

    /**
     * Laravel validation rules for the block's data, keyed by field.
     *
     * @return array<string, array<int, mixed>|string>
     */
    abstract public function rules(): array;

    public function icon(): string|BackedEnum
    {
        return Heroicon::OutlinedSquares2x2;
    }

    /**
     * Hosts allowed to use this block. Defaults to every host.
     *
     * @return array<int, string>
     */
    public function hosts(): array
    {
        return self::HOSTS;
    }

    public function allowedOn(string $host): bool
    {
        return in_array($host, $this->hosts(), true);
    }

    public function toFilamentBlock(): FilamentBlock
    {
        return FilamentBlock::make($this->key())
            ->label($this->label())
            ->icon($this->icon())
            ->schema([
                ...$this->fields(),
                $this->displaySettings(),
            ]);
    }

    /**
     * Complete validation rules including the shared display settings.
     *
     * @return array<string, array<int, mixed>|string>
     */
    public function allRules(): array
    {
        return $this->rules() + [
            'is_enabled' => ['sometimes', 'boolean'],
            'theme' => ['sometimes', 'nullable', 'in:'.implode(',', self::THEMES)],
            'anchor' => ['sometimes', 'nullable', 'string', 'max:64', 'regex:/^[a-z0-9-]+$/'],
        ];
    }

    protected function displaySettings(): Grid
    {
        return Grid::make(3)->schema([
            Toggle::make('is_enabled')->label('Enabled')->default(true)->inline(false),
            Select::make('theme')->options(array_combine(self::THEMES, ['Light', 'Dark', 'Neutral']))->default('light')->native(false),
            TextInput::make('anchor')->label('Anchor id')->helperText('Optional, letters, numbers and dashes only.')->regex('/^[a-z0-9-]+$/')->maxLength(64),
        ]);
    }
}
