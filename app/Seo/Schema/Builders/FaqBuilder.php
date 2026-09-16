<?php

namespace App\Seo\Schema\Builders;

use App\Models\Faq;
use App\Seo\Schema\Concerns\OmitsEmptyValues;
use App\Seo\Schema\SchemaBuilder;
use App\Seo\Schema\SchemaContext;

/**
 * FAQPage only for FAQs the page actually renders (Phase 6 §14).
 */
class FaqBuilder implements SchemaBuilder
{
    use OmitsEmptyValues;

    public function supports(SchemaContext $context): bool
    {
        return $context->faqs !== null && $context->faqs->isNotEmpty();
    }

    public function build(SchemaContext $context): array
    {
        $questions = $context->faqs
            ->filter(fn (Faq $faq): bool => $faq->is_visible && filled($this->text($faq->question)) && filled($this->text($faq->answer)))
            ->unique('id')
            ->values()
            ->map(fn (Faq $faq): array => [
                '@type' => 'Question',
                'name' => $this->text($faq->question),
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $this->text($faq->answer)],
            ])
            ->all();

        if ($questions === []) {
            return [];
        }

        return [[
            '@type' => 'FAQPage',
            '@id' => $context->url.'#faq',
            'mainEntity' => $questions,
        ]];
    }
}
