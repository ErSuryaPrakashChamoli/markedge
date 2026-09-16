<?php

namespace App\Cms\Blocks\Types;

use App\Cms\Blocks\Block;
use App\Cms\Blocks\Fields;
use App\Models\Form;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Support\Icons\Heroicon;

class ContactForm extends Block
{
    public function key(): string
    {
        return 'contact_form';
    }

    public function label(): string
    {
        return 'Contact form';
    }

    public function icon(): Heroicon
    {
        return Heroicon::OutlinedPaperAirplane;
    }

    public function hosts(): array
    {
        return ['page'];
    }

    public function fields(): array
    {
        return [
            Select::make('form_id')->label('Form')->options(fn () => Form::query()->active()->orderBy('name')->pluck('name', 'id'))->searchable()->native(false)->required(),
            Fields::heading(),
            Fields::intro(),
            Toggle::make('show_contact_details')->label('Show email, phone and address beside the form')->default(true),
        ];
    }

    public function rules(): array
    {
        return [
            'form_id' => ['required', 'integer'],
            'heading' => ['nullable', 'string', 'max:255'],
            'intro' => ['nullable', 'string', 'max:500'],
            'show_contact_details' => ['sometimes', 'boolean'],
        ];
    }
}
