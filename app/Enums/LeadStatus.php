<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Sales lifecycle. Open stages progress left to right; the allowed moves between stages live in
 * config/markedge.php (sales.transitions) so the pipeline can be tuned without a code change.
 * "converted" is the stored value of the Won stage: Phase 8 data and reports keep working unchanged.
 */
enum LeadStatus: string implements HasColor, HasLabel
{
    case New = 'new';
    case Assigned = 'assigned';
    case Contacted = 'contacted';
    case Qualified = 'qualified';
    case RequirementUnderstood = 'requirement_understood';
    case Proposal = 'proposal';
    case Negotiation = 'negotiation';
    case Converted = 'converted';
    case Lost = 'lost';
    case Unqualified = 'unqualified';
    case Spam = 'spam';

    /**
     * Open pipeline stages in order.
     *
     * @return array<int, self>
     */
    public static function pipeline(): array
    {
        return [self::New, self::Assigned, self::Contacted, self::Qualified, self::RequirementUnderstood, self::Proposal, self::Negotiation];
    }

    /**
     * @return array<int, self>
     */
    public static function closed(): array
    {
        return [self::Converted, self::Lost, self::Unqualified, self::Spam];
    }

    /**
     * Stages at or beyond qualification, including Won. Used by funnel reporting.
     *
     * @return array<int, self>
     */
    public static function qualifiedOrBeyond(): array
    {
        return [self::Qualified, self::RequirementUnderstood, self::Proposal, self::Negotiation, self::Converted];
    }

    /**
     * @return array<int, string>
     */
    public static function values(array $cases): array
    {
        return array_map(fn (self $case): string => $case->value, $cases);
    }

    public function isOpen(): bool
    {
        return in_array($this, self::pipeline(), true);
    }

    public function isClosed(): bool
    {
        return ! $this->isOpen();
    }

    public function isWon(): bool
    {
        return $this === self::Converted;
    }

    public function isLost(): bool
    {
        return $this === self::Lost || $this === self::Unqualified;
    }

    public function requiresReason(): bool
    {
        return $this->isLost();
    }

    public function isQualifiedOrBeyond(): bool
    {
        return in_array($this, self::qualifiedOrBeyond(), true);
    }

    public function stageIndex(): ?int
    {
        $index = array_search($this, self::pipeline(), true);

        return $index === false ? null : $index;
    }

    /**
     * Stages this one may move to, from configuration. Spam is always reachable and is not listed.
     *
     * @return array<int, self>
     */
    public function transitions(): array
    {
        $configured = config('markedge.sales.transitions.'.$this->value, []);

        return array_values(array_filter(array_map(
            fn ($value) => is_string($value) ? self::tryFrom($value) : null,
            is_array($configured) ? $configured : [],
        )));
    }

    public function canTransitionTo(self $to): bool
    {
        return $to === self::Spam || in_array($to, $this->transitions(), true);
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Assigned => 'Assigned',
            self::Contacted => 'Contacted',
            self::Qualified => 'Qualified',
            self::RequirementUnderstood => 'Requirement understood',
            self::Proposal => 'Proposal',
            self::Negotiation => 'Negotiation',
            self::Converted => 'Won',
            self::Lost => 'Lost',
            self::Unqualified => 'Unqualified',
            self::Spam => 'Spam',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::New => 'info',
            self::Assigned => 'info',
            self::Contacted => 'warning',
            self::Qualified => 'success',
            self::RequirementUnderstood => 'warning',
            self::Proposal => 'primary',
            self::Negotiation => 'primary',
            self::Converted => 'success',
            self::Lost => 'danger',
            self::Unqualified => 'gray',
            self::Spam => 'danger',
        };
    }
}
