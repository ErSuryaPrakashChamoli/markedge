<?php

namespace App\Cms\Blocks\Types;

use App\Cms\Blocks\Block;
use App\Cms\Blocks\Fields;
use Filament\Forms\Components\Placeholder;
use Filament\Support\Icons\Heroicon;

/**
 * Newsletter sign-up card. Empty fields fall back to Global Settings → Newsletter; a page that places
 * this block does not repeat the footer newsletter band.
 */
class Newsletter extends Block
{
    public function key(): string
    {
        return 'newsletter';
    }

    public function label(): string
    {
        return 'Newsletter sign-up';
    }

    public function icon(): Heroicon
    {
        return Heroicon::OutlinedEnvelope;
    }

    public function fields(): array
    {
        return [
            Placeholder::make('newsletter_help')->hiddenLabel()->columnSpanFull()
                ->content('Leave the fields empty to use the wording from Global Settings → Newsletter. Sign-ups appear under Marketing → Newsletter subscribers.'),
            Fields::heading(),
            Fields::intro(),
            Fields::ctaLabel('button_label', 'Button label'),
        ];
    }

    public function rules(): array
    {
        return [
            'heading' => ['nullable', 'string', 'max:255'],
            'intro' => ['nullable', 'string', 'max:500'],
            'button_label' => ['nullable', 'string', 'max:80'],
        ];
    }
}
