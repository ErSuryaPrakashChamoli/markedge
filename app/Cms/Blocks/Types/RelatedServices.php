<?php

namespace App\Cms\Blocks\Types;

use App\Cms\Blocks\Block;
use App\Cms\Blocks\Fields;
use App\Models\Service;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;

class RelatedServices extends Block
{
    public function key(): string
    {
        return 'related_services';
    }

    public function label(): string
    {
        return 'Related services';
    }

    public function icon(): Heroicon
    {
        return Heroicon::OutlinedWrenchScrewdriver;
    }

    public function hosts(): array
    {
        return ['service', 'product', 'solution', 'industry', 'case_study', 'page'];
    }

    public function fields(): array
    {
        return [
            Fields::heading(),
            Fields::mode(['auto' => 'Automatic from relationships', 'ids' => 'Selected services'], 'auto'),
            Fields::records('service_ids', 'Services', Service::class)->visible(fn (Get $get): bool => $get('mode') === 'ids'),
            Fields::limit(4, 8),
        ];
    }

    public function rules(): array
    {
        return [
            'heading' => ['nullable', 'string', 'max:255'],
            'mode' => ['required', 'in:auto,ids'],
            'service_ids' => ['required_if:mode,ids', 'nullable', 'array'],
            'service_ids.*' => ['integer'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:8'],
        ];
    }
}
