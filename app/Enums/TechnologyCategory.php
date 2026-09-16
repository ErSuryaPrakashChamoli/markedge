<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TechnologyCategory: string implements HasLabel
{
    case Frontend = 'frontend';
    case Backend = 'backend';
    case Mobile = 'mobile';
    case Database = 'database';
    case Infrastructure = 'infrastructure';
    case Cloud = 'cloud';
    case Ai = 'ai';
    case Devops = 'devops';
    case Tooling = 'tooling';

    public function getLabel(): string
    {
        return match ($this) {
            self::Frontend => 'Frontend',
            self::Backend => 'Backend',
            self::Mobile => 'Mobile',
            self::Database => 'Database',
            self::Infrastructure => 'Infrastructure',
            self::Cloud => 'Cloud',
            self::Ai => 'AI',
            self::Devops => 'DevOps',
            self::Tooling => 'Tooling',
        };
    }
}
