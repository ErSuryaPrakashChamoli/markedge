<?php

namespace Database\Seeders;

use App\Enums\FormFieldType;
use App\Enums\FormType;
use App\Models\Form;
use Illuminate\Database\Seeder;

/**
 * The eight standard forms with their core-field configuration and extra fields.
 */
class FormSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->definitions() as $key => $definition) {
            $fields = $definition['fields'] ?? [];
            unset($definition['fields']);

            $form = Form::query()->withTrashed()->firstOrNew(['key' => $key]);
            $form->fill($definition + ['success_message' => 'Thank you. A member of the Markedge team will get back to you shortly.']);
            $form->save();

            foreach ($fields as $index => $field) {
                $form->fields()->updateOrCreate(['key' => $field['key']], $field + ['sort_order' => $index]);
            }
        }
    }

    /**
     * @param  array<int, string>  $enabled
     * @param  array<int, string>  $required
     * @return array<string, array{enabled: bool, required: bool, sort_order: int}>
     */
    protected function core(array $enabled, array $required): array
    {
        $config = [];

        foreach (Form::CORE_FIELDS as $index => $field) {
            $config[$field] = [
                'enabled' => in_array($field, $enabled, true),
                'required' => in_array($field, $required, true),
                'sort_order' => $index,
            ];
        }

        return $config;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected function definitions(): array
    {
        return [
            'general-enquiry' => [
                'name' => 'General Enquiry',
                'type' => FormType::GeneralEnquiry,
                'heading' => 'Start a conversation',
                'submit_label' => 'Send enquiry',
                'core_fields' => $this->core(['name', 'email', 'phone', 'company', 'message'], ['name', 'email', 'message']),
            ],
            'consultation' => [
                'name' => 'Consultation Request',
                'type' => FormType::Consultation,
                'heading' => 'Request a consultation',
                'submit_label' => 'Request consultation',
                'core_fields' => $this->core(['name', 'email', 'phone', 'company', 'requirement'], ['name', 'email', 'phone', 'requirement']),
                'fields' => [
                    ['key' => 'service', 'label' => 'Area of interest', 'type' => FormFieldType::Select, 'options' => ['source' => 'services'], 'maps_to' => 'service_id', 'is_required' => false],
                ],
            ],
            'quote-request' => [
                'name' => 'Quote Request',
                'type' => FormType::Quote,
                'heading' => 'Request a quote',
                'submit_label' => 'Request quote',
                'core_fields' => $this->core(['name', 'email', 'phone', 'company', 'requirement'], ['name', 'email', 'requirement']),
                'fields' => [
                    ['key' => 'service', 'label' => 'Service', 'type' => FormFieldType::Select, 'options' => ['source' => 'services'], 'maps_to' => 'service_id', 'is_required' => false],
                    ['key' => 'timeline', 'label' => 'Expected timeline', 'type' => FormFieldType::Text, 'is_required' => false],
                ],
            ],
            'product-demo' => [
                'name' => 'Product Demo',
                'type' => FormType::ProductDemo,
                'heading' => 'Book a product demo',
                'submit_label' => 'Book demo',
                'core_fields' => $this->core(['name', 'email', 'phone', 'company'], ['name', 'email', 'phone', 'company']),
                'fields' => [
                    ['key' => 'product', 'label' => 'Product', 'type' => FormFieldType::Select, 'options' => ['source' => 'products'], 'maps_to' => 'product_id', 'is_required' => true],
                    ['key' => 'team_size', 'label' => 'Team size', 'type' => FormFieldType::Select, 'options' => ['values' => ['1-10', '11-50', '51-200', '200+']], 'is_required' => false],
                ],
            ],
            'it-assessment' => [
                'name' => 'IT Assessment',
                'type' => FormType::ItAssessment,
                'heading' => 'Request an IT assessment',
                'submit_label' => 'Request assessment',
                'core_fields' => $this->core(['name', 'email', 'phone', 'company', 'city', 'message'], ['name', 'email', 'phone', 'company']),
                'fields' => [
                    ['key' => 'user_count', 'label' => 'Number of users / devices', 'type' => FormFieldType::Number, 'is_required' => false],
                ],
            ],
            'digital-growth-audit' => [
                'name' => 'Digital Growth Audit',
                'type' => FormType::DigitalGrowthAudit,
                'heading' => 'Get a digital growth audit',
                'submit_label' => 'Request audit',
                'core_fields' => $this->core(['name', 'email', 'phone', 'company'], ['name', 'email', 'company']),
                'fields' => [
                    ['key' => 'website_url', 'label' => 'Website URL', 'type' => FormFieldType::Text, 'validation' => ['url' => true], 'is_required' => true],
                ],
            ],
            'early-access' => [
                'name' => 'Product Early Access',
                'type' => FormType::EarlyAccess,
                'heading' => 'Request early access',
                'submit_label' => 'Request early access',
                'core_fields' => $this->core(['name', 'email', 'company'], ['name', 'email']),
                'fields' => [
                    ['key' => 'product', 'label' => 'Product', 'type' => FormFieldType::Select, 'options' => ['source' => 'products'], 'maps_to' => 'product_id', 'is_required' => true],
                ],
            ],
            'contact-sales' => [
                'name' => 'Contact Sales',
                'type' => FormType::ContactSales,
                'heading' => 'Talk to sales',
                'submit_label' => 'Contact sales',
                'core_fields' => $this->core(['name', 'email', 'phone', 'company', 'message'], ['name', 'email', 'phone']),
            ],
        ];
    }
}
