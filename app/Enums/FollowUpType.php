<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum FollowUpType: string implements HasLabel
{
    case Call = 'call';
    case Email = 'email';
    case Meeting = 'meeting';
    case Task = 'task';

    public function getLabel(): string
    {
        return ucfirst($this->value);
    }
}
