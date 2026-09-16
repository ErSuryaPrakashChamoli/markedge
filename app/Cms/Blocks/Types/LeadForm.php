<?php

namespace App\Cms\Blocks\Types;

use App\Cms\Blocks\Block;
use App\Cms\Blocks\Fields;
use App\Models\Form;
use App\Models\Product;
use App\Models\Service;
use Filament\Forms\Components\Select;
use Filament\Support\Icons\Heroicon;

class LeadForm extends Block
{
    public function key(): string
    {
        return 'lead_form';
    }

    public function label(): string
    {
        return 'Lead form';
    }

    public function icon(): Heroicon
    {
        return Heroicon::OutlinedInboxArrowDown;
    }

    public function fields(): array
    {
        return [
            Select::make('form_id')->label('Form')->options(fn () => Form::query()->active()->orderBy('name')->pluck('name', 'id'))->searchable()->native(false)
                ->helperText('Leave empty on a landing page to use the landing page\'s own form.'),
            Fields::heading(),
            Fields::intro(),
            Fields::layout(['inline' => 'Inline', 'card' => 'Card'], 'card'),
            Select::make('preselect_service_id')->label('Preselect service')->options(fn () => Service::query()->orderBy('name')->pluck('name', 'id'))->searchable()->native(false),
            Select::make('preselect_product_id')->label('Preselect product')->options(fn () => Product::query()->orderBy('name')->pluck('name', 'id'))->searchable()->native(false),
        ];
    }

    public function rules(): array
    {
        return [
            'form_id' => ['nullable', 'integer'],
            'heading' => ['nullable', 'string', 'max:255'],
            'intro' => ['nullable', 'string', 'max:500'],
            'layout' => ['nullable', 'in:inline,card'],
            'preselect_service_id' => ['nullable', 'integer'],
            'preselect_product_id' => ['nullable', 'integer'],
        ];
    }
}
