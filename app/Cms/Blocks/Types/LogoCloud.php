<?php

namespace App\Cms\Blocks\Types;

use App\Cms\Blocks\Block;
use App\Cms\Blocks\Fields;
use App\Models\Client;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;

/**
 * Renders only clients marked visible; shows nothing when none exist.
 */
class LogoCloud extends Block
{
    public function key(): string
    {
        return 'logo_cloud';
    }

    public function label(): string
    {
        return 'Client logos';
    }

    public function icon(): Heroicon
    {
        return Heroicon::OutlinedBriefcase;
    }

    public function fields(): array
    {
        return [
            Fields::heading(),
            Fields::mode(['all_visible' => 'All clients marked for the logo cloud', 'ids' => 'Selected clients'], 'all_visible'),
            Fields::records('client_ids', 'Clients', Client::class)->visible(fn (Get $get): bool => $get('mode') === 'ids'),
        ];
    }

    public function rules(): array
    {
        return [
            'heading' => ['nullable', 'string', 'max:255'],
            'mode' => ['required', 'in:all_visible,ids'],
            'client_ids' => ['required_if:mode,ids', 'nullable', 'array'],
            'client_ids.*' => ['integer'],
        ];
    }
}
