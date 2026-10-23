<?php

namespace App\Cms\Blocks\Types;

use App\Cms\Blocks\Block;
use App\Cms\Blocks\Fields;
use Filament\Support\Icons\Heroicon;

/**
 * The grievance escalation ladder. Levels and their contact persons are managed under Website → Grievance Levels.
 */
class GrievanceRedressal extends Block
{
    public function key(): string
    {
        return 'grievance';
    }

    public function label(): string
    {
        return 'Grievance redressal';
    }

    public function icon(): Heroicon
    {
        return Heroicon::OutlinedScale;
    }

    public function hosts(): array
    {
        return ['page'];
    }

    public function fields(): array
    {
        return [
            Fields::heading()->default('Grievance redressal')->helperText('Levels and contact persons come from Website → Grievance Levels.'),
            Fields::intro(),
            Fields::richText('note', 'Note')
                ->helperText('Optional. Shown above the levels and contact persons, e.g. the steps to raise a complaint, response timelines, or where complaints about a partner should go.'),
        ];
    }

    public function rules(): array
    {
        return [
            'heading' => ['nullable', 'string', 'max:255'],
            'intro' => ['nullable', 'string', 'max:500'],
            'note' => ['nullable', 'string'],
        ];
    }
}
