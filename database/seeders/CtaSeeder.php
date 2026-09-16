<?php

namespace Database\Seeders;

use App\Enums\CtaAction;
use App\Enums\CtaVariant;
use App\Models\Cta;
use Illuminate\Database\Seeder;

/**
 * Reusable CTA definitions. Labels come from the brief; every value is editable in the admin.
 */
class CtaSeeder extends Seeder
{
    public function run(): void
    {
        $ctas = [
            'start-conversation' => [
                'name' => 'Start a Conversation',
                'headline' => "Let's build what comes next.",
                'primary_label' => 'Start a Conversation',
                'primary_action' => CtaAction::Url,
                'primary_value' => '/contact',
                'secondary_label' => 'Request a Consultation',
                'secondary_action' => CtaAction::Url,
                'secondary_value' => '/request-consultation',
                'variant' => CtaVariant::Band,
            ],
            'request-consultation' => [
                'name' => 'Request a Consultation',
                'primary_label' => 'Request a Consultation',
                'primary_action' => CtaAction::Url,
                'primary_value' => '/request-consultation',
                'variant' => CtaVariant::Inline,
            ],
            'request-quote' => [
                'name' => 'Request a Quote',
                'primary_label' => 'Request a Quote',
                'primary_action' => CtaAction::Url,
                'primary_value' => '/request-quote',
                'variant' => CtaVariant::Inline,
            ],
            'book-demo' => [
                'name' => 'Book a Demo',
                'primary_label' => 'Book a Demo',
                'primary_action' => CtaAction::Url,
                'primary_value' => '/request-demo',
                'secondary_label' => 'WhatsApp us',
                'secondary_action' => CtaAction::Whatsapp,
                'whatsapp_message' => 'Hi Markedge, I would like to request a demo of your {entity}.',
                'variant' => CtaVariant::Band,
            ],
            'request-it-assessment' => [
                'name' => 'Request IT Assessment',
                'primary_label' => 'Request IT Assessment',
                'primary_action' => CtaAction::Url,
                'primary_value' => '/request-it-assessment',
                'variant' => CtaVariant::Inline,
            ],
            'digital-growth-audit' => [
                'name' => 'Get a Digital Growth Audit',
                'primary_label' => 'Get a Digital Growth Audit',
                'primary_action' => CtaAction::Url,
                'primary_value' => '/request-digital-growth-audit',
                'variant' => CtaVariant::Inline,
            ],
            'talk-to-sales' => [
                'name' => 'Talk to Sales',
                'primary_label' => 'Talk to Sales',
                'primary_action' => CtaAction::Url,
                'primary_value' => '/contact',
                'secondary_label' => 'WhatsApp us',
                'secondary_action' => CtaAction::Whatsapp,
                'whatsapp_message' => 'Hi Markedge, I would like to discuss a {entity} requirement.',
                'variant' => CtaVariant::Card,
            ],
            'talk-to-us' => [
                'name' => 'Talk to Us (header)',
                'primary_label' => 'Talk to Us',
                'primary_action' => CtaAction::Url,
                'primary_value' => '/contact',
                'variant' => CtaVariant::Inline,
            ],
        ];

        foreach ($ctas as $key => $attributes) {
            Cta::query()->updateOrCreate(['key' => $key], $attributes);
        }
    }
}
