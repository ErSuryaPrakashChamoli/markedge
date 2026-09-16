<?php

namespace App\Cms\Blocks\Types;

use App\Cms\Blocks\Block;
use App\Cms\Blocks\Fields;
use App\Models\Faq as FaqModel;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;

class Faq extends Block
{
    public function key(): string
    {
        return 'faq';
    }

    public function label(): string
    {
        return 'FAQ';
    }

    public function icon(): Heroicon
    {
        return Heroicon::OutlinedQuestionMarkCircle;
    }

    public function fields(): array
    {
        return [
            Fields::heading(),
            Fields::intro(),
            Fields::mode(['host_faqs' => 'FAQs attached to this page', 'ids' => 'Selected FAQs'], 'host_faqs'),
            Fields::records('faq_ids', 'FAQs', FaqModel::class, 'question')->visible(fn (Get $get): bool => $get('mode') === 'ids'),
        ];
    }

    public function rules(): array
    {
        return [
            'heading' => ['nullable', 'string', 'max:255'],
            'intro' => ['nullable', 'string', 'max:500'],
            'mode' => ['required', 'in:host_faqs,ids'],
            'faq_ids' => ['required_if:mode,ids', 'nullable', 'array'],
            'faq_ids.*' => ['integer'],
        ];
    }
}
