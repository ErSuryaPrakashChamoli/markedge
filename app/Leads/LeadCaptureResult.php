<?php

namespace App\Leads;

use App\Models\Lead;

final readonly class LeadCaptureResult
{
    public function __construct(
        public Lead $lead,
        public bool $created,
        public bool $duplicate = false,
    ) {}
}
