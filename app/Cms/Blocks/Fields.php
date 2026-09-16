<?php

namespace App\Cms\Blocks;

use App\Models\Cta;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Model;

/**
 * Field factories shared by block types so every block asks for content the same way.
 */
class Fields
{
    /** @var array<int, string> */
    public const array IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    public static function heading(string $name = 'heading', bool $required = false): TextInput
    {
        return TextInput::make($name)->label('Heading')->required($required)->maxLength(255)->columnSpanFull();
    }

    public static function intro(string $name = 'intro'): TextInput
    {
        return TextInput::make($name)->label('Intro')->maxLength(500)->columnSpanFull();
    }

    public static function richText(string $name = 'body', string $label = 'Body', bool $required = false): RichEditor
    {
        return RichEditor::make($name)
            ->label($label)
            ->required($required)
            ->toolbarButtons(['bold', 'italic', 'underline', 'h2', 'h3', 'bulletList', 'orderedList', 'link', 'blockquote'])
            ->columnSpanFull();
    }

    public static function image(string $name = 'image', string $label = 'Image'): FileUpload
    {
        return FileUpload::make($name)
            ->label($label)
            ->image()
            ->disk('public')
            ->directory('blocks')
            ->visibility('public')
            ->acceptedFileTypes(self::IMAGE_TYPES)
            ->maxSize(5120)
            ->imageEditor();
    }

    public static function imageAlt(string $name = 'image_alt'): TextInput
    {
        return TextInput::make($name)->label('Image alt text')->maxLength(255);
    }

    public static function cta(string $name = 'cta_id', string $label = 'Call to action'): Select
    {
        return Select::make($name)
            ->label($label)
            ->options(fn () => Cta::query()->active()->orderBy('name')->pluck('name', 'id'))
            ->searchable()
            ->native(false);
    }

    public static function ctaLabel(string $name = 'cta_label', string $label = 'Button label'): TextInput
    {
        return TextInput::make($name)->label($label)->maxLength(80);
    }

    public static function link(string $name = 'cta_url', string $label = 'Button link'): TextInput
    {
        return TextInput::make($name)
            ->label($label)
            ->maxLength(500)
            ->helperText('A site path such as /services or a full https:// URL.')
            ->rules(['nullable', 'regex:#^(/[^\s]*|https?://[^\s]+)$#']);
    }

    /**
     * @param  class-string<Model>  $model
     */
    public static function records(string $name, string $label, string $model, string $titleAttribute = 'name'): Select
    {
        return Select::make($name)
            ->label($label)
            ->multiple()
            ->searchable()
            ->getSearchResultsUsing(fn (string $search) => $model::query()
                ->where($titleAttribute, 'like', "%{$search}%")
                ->limit(25)
                ->pluck($titleAttribute, 'id'))
            ->getOptionLabelsUsing(fn (array $values) => $model::query()->whereKey($values)->pluck($titleAttribute, 'id'))
            ->columnSpanFull();
    }

    public static function limit(int $default = 6, int $max = 24): TextInput
    {
        return TextInput::make('limit')->label('Maximum items')->numeric()->default($default)->minValue(1)->maxValue($max);
    }

    /**
     * @param  array<string, string>  $options
     */
    public static function mode(array $options, string $default): Select
    {
        return Select::make('mode')->label('Source')->options($options)->default($default)->required()->native(false)->live();
    }

    /**
     * @param  array<string, string>  $options
     */
    public static function layout(array $options, string $default, string $name = 'layout'): Select
    {
        return Select::make($name)->label('Layout')->options($options)->default($default)->native(false);
    }

    /**
     * Rule fragment for a block image path stored by FileUpload.
     *
     * @return array<int, string>
     */
    public static function imageRule(): array
    {
        return ['nullable', 'string', 'max:500'];
    }

    /**
     * @return array<int, string>
     */
    public static function linkRule(): array
    {
        return ['nullable', 'string', 'max:500', 'regex:#^(/[^\s]*|https?://[^\s]+)$#'];
    }
}
