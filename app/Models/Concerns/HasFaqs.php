<?php

namespace App\Models\Concerns;

use App\Models\Faq;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasFaqs
{
    public function faqs(): MorphMany
    {
        return $this->morphMany(Faq::class, 'faqable')->orderBy('sort_order')->orderBy('id');
    }
}
