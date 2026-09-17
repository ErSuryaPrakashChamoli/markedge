<?php

namespace App\Ai\Contracts;

use App\Ai\AiResponse;
use App\Models\Lead;

/**
 * Summarises a lead's enquiry, timeline and qualification for a sales user.
 * Implementations receive only the lead the caller is authorised to view.
 */
interface LeadSummarizer
{
    public function summarize(Lead $lead): AiResponse;
}
