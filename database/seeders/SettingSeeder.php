<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Global settings with safe defaults. Contact details stay empty until Markedge supplies them.
 */
class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // group => [key => [value, type]]
            'company' => [
                'company.name' => ['Markedge Technologies', 'text'],
                'company.tagline' => ['Build. Operate. Grow.', 'text'],
                'company.legal_name' => [null, 'text'],
                'company.description' => ['[PLACEHOLDER: Markedge company description]', 'textarea'],
            ],
            'contact' => [
                'contact.email' => [null, 'email'],
                'contact.phone' => [null, 'phone'],
                'contact.whatsapp' => [null, 'phone'],
                'contact.address' => [null, 'textarea'],
                'contact.city' => [null, 'text'],
                'contact.country' => [null, 'text'],
            ],
            'seo' => [
                'seo.title_suffix' => [' | Markedge Technologies', 'text'],
                'seo.default_title' => ['Markedge Technologies — Build. Operate. Grow.', 'text'],
                'seo.default_description' => ['[PLACEHOLDER: default meta description]', 'textarea'],
                'seo.canonical_host' => [null, 'url'],
                'seo.twitter_handle' => [null, 'text'],
            ],
            'schema' => [
                'schema.local_business_enabled' => [false, 'boolean'],
            ],
            'tracking' => [
                'tracking.gtm_container_id' => [null, 'text'],
                'tracking.ga4_measurement_id' => [null, 'text'],
                'tracking.meta_pixel_id' => [null, 'text'],
                'tracking.linkedin_partner_id' => [null, 'text'],
                'tracking.google_site_verification' => [null, 'text'],
            ],
            'cta' => [
                'cta.header' => ['talk-to-us', 'text'],
                'cta.default' => ['start-conversation', 'text'],
                'cta.default_service' => ['request-consultation', 'text'],
                'cta.default_product' => ['book-demo', 'text'],
                'cta.default_article' => ['start-conversation', 'text'],
            ],
            'leads' => [
                'leads.notify_emails' => [[], 'json'],
            ],
            'privacy' => [
                'privacy.attribution_requires_consent' => [false, 'boolean'],
                'privacy.cookie_banner_enabled' => [true, 'boolean'],
            ],
        ];

        foreach ($settings as $group => $entries) {
            foreach ($entries as $key => [$value, $type]) {
                Setting::query()->firstOrCreate(['key' => $key], ['group' => $group, 'value' => $value, 'type' => $type]);
            }
        }
    }
}
