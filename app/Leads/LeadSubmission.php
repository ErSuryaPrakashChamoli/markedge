<?php

namespace App\Leads;

use App\Attribution\Attribution;
use App\Models\Form;

/**
 * Everything the capture service needs, already validated and resolved server-side.
 * Relationship ids come from trusted context or validated option lists, never raw hidden fields.
 */
final readonly class LeadSubmission
{
    /**
     * @param  array<string, mixed>  $core  validated core lead columns
     * @param  array<string, mixed>  $custom  validated extra answers keyed by field label
     * @param  array<string, int|null>  $relations  service_id, product_id, industry_id, solution_id, landing_page_id
     */
    public function __construct(
        public Form $form,
        public array $core,
        public array $custom,
        public array $relations,
        public Attribution $attribution,
        public ?string $conversionPath,
        public bool $consentGiven,
        public string $submissionToken,
        public ?string $userAgent = null,
        public ?string $ip = null,
        public ?string $locale = null,
    ) {}
}
