<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EditorialCommentType: string implements HasColor, HasLabel
{
    case Comment = 'comment';
    case ChangeRequest = 'change_request';

    public function getLabel(): string
    {
        return match ($this) {
            self::Comment => 'Comment',
            self::ChangeRequest => 'Changes requested',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Comment => 'gray',
            self::ChangeRequest => 'warning',
        };
    }
}
