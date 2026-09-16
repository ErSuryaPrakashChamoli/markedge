<?php

namespace Database\Seeders;

use App\Enums\PublishStatus;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;

/**
 * The three capability pillars and the service names Markedge offers.
 * Everything is created as a draft: copy and publishing are editorial decisions.
 */
class ServiceCatalogueSeeder extends Seeder
{
    /**
     * @var array<string, array{name: string, pillar_label: string, services: array<string, string>}>
     */
    protected array $catalogue = [
        'technology' => [
            'name' => 'Technology',
            'pillar_label' => 'BUILD',
            'services' => [
                'software-development' => 'Software Development',
                'web-development' => 'Web Development',
                'mobile-app-development' => 'Mobile Application Development',
                'product-development' => 'Product Development',
                'custom-business-applications' => 'Custom Business Applications',
                'api-development' => 'API Development & Integration',
                'ai-automation' => 'AI & Automation',
                'ui-ux-design' => 'UI/UX Design',
                'cloud-solutions' => 'Cloud Solutions',
            ],
        ],
        'it-infrastructure' => [
            'name' => 'IT Infrastructure',
            'pillar_label' => 'OPERATE',
            'services' => [
                'it-amc' => 'IT AMC',
                'networking' => 'Networking',
                'server-infrastructure-management' => 'Server & Infrastructure Management',
                'cloud' => 'Cloud Infrastructure',
                'cybersecurity' => 'Cybersecurity',
                'backup-disaster-recovery' => 'Backup & Disaster Recovery',
                'it-support' => 'IT Support',
            ],
        ],
        'digital-growth' => [
            'name' => 'Digital Growth',
            'pillar_label' => 'GROW',
            'services' => [
                'digital-marketing' => 'Digital Marketing',
                'seo' => 'SEO',
                'social-media-marketing' => 'Social Media Marketing',
                'performance-marketing' => 'Performance Marketing',
                'content-marketing' => 'Content Marketing',
                'branding' => 'Branding',
                'lead-generation' => 'Lead Generation',
                'conversion-optimisation' => 'Conversion Optimisation',
            ],
        ],
    ];

    public function run(): void
    {
        $categorySort = 0;

        foreach ($this->catalogue as $categorySlug => $definition) {
            $category = ServiceCategory::query()->withTrashed()->firstOrNew(['slug' => $categorySlug]);
            $category->fill([
                'name' => $definition['name'],
                'pillar_label' => $definition['pillar_label'],
                'sort_order' => $categorySort++,
            ]);
            $category->status ??= PublishStatus::Draft;
            $category->save();

            $serviceSort = 0;

            foreach ($definition['services'] as $serviceSlug => $serviceName) {
                $service = Service::query()->withTrashed()->firstOrNew(['slug' => $serviceSlug]);
                $service->fill([
                    'service_category_id' => $category->id,
                    'name' => $serviceName,
                    'sort_order' => $serviceSort++,
                ]);
                $service->status ??= PublishStatus::Draft;
                $service->save();
            }
        }
    }
}
