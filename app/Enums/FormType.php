<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum FormType: string implements HasLabel
{
    case GeneralEnquiry = 'general_enquiry';
    case Consultation = 'consultation';
    case Quote = 'quote';
    case ProductDemo = 'product_demo';
    case ItAssessment = 'it_assessment';
    case DigitalGrowthAudit = 'digital_growth_audit';
    case EarlyAccess = 'early_access';
    case ContactSales = 'contact_sales';

    public function getLabel(): string
    {
        return match ($this) {
            self::GeneralEnquiry => 'General enquiry',
            self::Consultation => 'Consultation request',
            self::Quote => 'Quote request',
            self::ProductDemo => 'Product demo',
            self::ItAssessment => 'IT assessment',
            self::DigitalGrowthAudit => 'Digital growth audit',
            self::EarlyAccess => 'Product early access',
            self::ContactSales => 'Contact sales',
        };
    }
}
