<?php

namespace App\Seo\Schema\Builders;

use App\Models\SocialLink;
use App\Seo\Schema\Concerns\OmitsEmptyValues;
use App\Seo\Schema\SchemaBuilder;
use App\Seo\Schema\SchemaContext;
use App\Services\Cms\Settings;

/**
 * Organization from Global Settings only. Address and contact details appear only when configured.
 */
class OrganizationBuilder implements SchemaBuilder
{
    use OmitsEmptyValues;

    public function __construct(private readonly Settings $settings) {}

    public function supports(SchemaContext $context): bool
    {
        return true;
    }

    public function build(SchemaContext $context): array
    {
        $address = $this->text($this->settings->get('contact.address'));

        $organization = $this->compact([
            '@type' => 'Organization',
            '@id' => $context->organizationId(),
            'name' => $this->settings->get('company.name', config('app.name')),
            'legalName' => $this->settings->get('company.legal_name'),
            'url' => $context->siteUrl,
            'logo' => $this->settings->logoUrl(),
            'description' => $this->text($this->settings->get('company.description')),
            'email' => $this->settings->get('contact.email'),
            'telephone' => $this->settings->get('contact.phone'),
            'sameAs' => SocialLink::query()->visible()->ordered()->pluck('url')->all(),
            'address' => $address ? $this->compact([
                '@type' => 'PostalAddress',
                'streetAddress' => $address,
                'addressLocality' => $this->settings->get('contact.city'),
                'addressCountry' => $this->settings->get('contact.country'),
            ]) : null,
        ]);

        $nodes = [$organization];

        if ($this->settings->get('schema.local_business_enabled') && $address && filled($this->settings->get('contact.phone'))) {
            $nodes[] = $this->compact([
                '@type' => 'LocalBusiness',
                '@id' => $context->siteUrl.'/#localbusiness',
                'name' => $organization['name'],
                'url' => $context->siteUrl,
                'telephone' => $this->settings->get('contact.phone'),
                'address' => $organization['address'],
                'parentOrganization' => ['@id' => $context->organizationId()],
            ]);
        }

        return $nodes;
    }
}
