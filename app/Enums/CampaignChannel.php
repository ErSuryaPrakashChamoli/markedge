<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CampaignChannel: string implements HasLabel
{
    case PaidSearch = 'paid_search';
    case PaidSocial = 'paid_social';
    case OrganicSocial = 'organic_social';
    case Email = 'email';
    case Referral = 'referral';
    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::PaidSearch => 'Paid search',
            self::PaidSocial => 'Paid social',
            self::OrganicSocial => 'Organic social',
            self::Email => 'Email',
            self::Referral => 'Referral',
            self::Other => 'Other',
        };
    }
}
