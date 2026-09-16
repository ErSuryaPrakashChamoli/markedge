<?php

namespace App\Cms\Blocks\Types;

use App\Cms\Blocks\Block;
use App\Cms\Blocks\Fields;
use Filament\Support\Icons\Heroicon;

class RichText extends Block
{
    public function key(): string
    {
        return 'rich_text';
    }

    public function label(): string
    {
        return 'Rich text';
    }

    public function icon(): Heroicon
    {
        return Heroicon::OutlinedDocumentText;
    }

    public function fields(): array
    {
        return [
            Fields::richText(required: true),
            Fields::layout(['narrow' => 'Reading width', 'normal' => 'Standard width'], 'narrow', 'width'),
        ];
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string'],
            'width' => ['nullable', 'in:narrow,normal'],
        ];
    }
}
