<?php

use App\Models\Article;
use App\Models\Author;
use App\Models\Faq;
use App\Models\Page;
use App\Models\Product;
use App\Models\SeoMeta;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Setting;
use App\Models\SocialLink;
use App\Seo\Schema\SchemaGraphBuilder;
use Illuminate\Testing\TestResponse;

function graphFrom(TestResponse $response): array
{
    preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $response->getContent(), $m);
    expect($m)->not->toBeEmpty('No JSON-LD script found');
    $decoded = json_decode(html_entity_decode($m[1]), true, 512, JSON_THROW_ON_ERROR);

    return collect($decoded['@graph'])->keyBy('@type')->all();
}

it('emits one coherent graph with organization, website, web page and breadcrumbs', function () {
    Setting::factory()->create(['key' => 'company.name', 'value' => 'Markedge Technologies']);
    Setting::factory()->create(['key' => 'contact.email', 'value' => 'hello@markedge.example']);
    SocialLink::factory()->create(['url' => 'https://www.linkedin.com/company/markedge']);
    SocialLink::factory()->hidden()->create(['url' => 'https://hidden.example']);
    $category = ServiceCategory::factory()->published()->create(['slug' => 'technology', 'name' => 'Technology']);
    $service = Service::factory()->for($category, 'category')->published()->create(['slug' => 'web-development', 'name' => 'Web Development', 'short_description' => 'Websites that work.']);

    $graph = graphFrom($this->get('/services/web-development'));

    expect($graph)->toHaveKeys(['Organization', 'WebSite', 'WebPage', 'BreadcrumbList', 'Service'])
        ->and($graph['Organization']['name'])->toBe('Markedge Technologies')
        ->and($graph['Organization']['email'])->toBe('hello@markedge.example')
        ->and($graph['Organization']['sameAs'])->toBe(['https://www.linkedin.com/company/markedge'])
        ->and($graph['Organization'])->not->toHaveKeys(['address', 'telephone', 'description'])
        ->and($graph['WebSite']['potentialAction']['target']['urlTemplate'])->toBe('http://localhost/search?q={search_term_string}')
        ->and($graph['WebPage']['url'])->toBe('http://localhost/services/web-development')
        ->and($graph['WebPage']['breadcrumb']['@id'])->toBe('http://localhost/services/web-development#breadcrumb')
        ->and($graph['Service']['provider']['@id'])->toBe('http://localhost/#organization')
        ->and($graph['Service']['category'])->toBe('Technology')
        ->and($graph['Service'])->not->toHaveKeys(['offers', 'aggregateRating', 'review', 'price']);

    $crumbs = array_column($graph['BreadcrumbList']['itemListElement'], 'name');
    expect($crumbs)->toBe(['Home', 'Services', 'Technology', 'Web Development'])
        ->and($graph['BreadcrumbList']['itemListElement'][2]['item'])->toBe('http://localhost/services/technology');
});

it('matches breadcrumb schema to the visible breadcrumb trail', function () {
    $category = ServiceCategory::factory()->published()->create(['name' => 'Digital Growth', 'slug' => 'digital-growth']);
    $service = Service::factory()->for($category, 'category')->published()->create(['name' => 'SEO', 'slug' => 'seo']);

    $response = $this->get('/services/seo')->assertSeeInOrder(['Home', 'Services', 'Digital Growth', 'SEO']);
    $graph = graphFrom($response);

    expect(array_column($graph['BreadcrumbList']['itemListElement'], 'name'))->toBe(['Home', 'Services', 'Digital Growth', 'SEO']);
});

it('emits article schema from real author, dates and category only', function () {
    $author = Author::factory()->create(['name' => 'Asha Writer', 'role_title' => 'Engineer', 'bio' => '<p>Bio</p>']);
    $article = Article::factory()->published()->create(['author_id' => $author->id, 'title' => 'Moving to the cloud', 'excerpt' => 'A summary.']);

    $graph = graphFrom($this->get('/insights/'.$article->slug));

    expect($graph['Article']['headline'])->toBe('Moving to the cloud')
        ->and($graph['Article']['author']['name'])->toBe('Asha Writer')
        ->and($graph['Article']['author']['url'])->toBe('http://localhost/insights/author/'.$author->slug)
        ->and($graph['Article']['datePublished'])->not->toBeNull()
        ->and($graph['Article']['publisher']['@id'])->toBe('http://localhost/#organization')
        ->and($graph['Article'])->not->toHaveKeys(['aggregateRating', 'review']);

    $anonymous = Article::factory()->published()->create(['author_id' => null]);
    expect(graphFrom($this->get('/insights/'.$anonymous->slug))['Article'])->not->toHaveKey('author');
});

it('emits a product as software application without commercial properties', function () {
    $product = Product::factory()->active()->create(['name' => 'Lead Management System', 'product_type' => 'Business platform']);

    $node = graphFrom($this->get('/products/'.$product->slug))['SoftwareApplication'];

    expect($node['name'])->toBe('Lead Management System')
        ->and($node['applicationCategory'])->toBe('Business platform')
        ->and($node)->not->toHaveKeys(['offers', 'price', 'aggregateRating', 'review', 'operatingSystem']);
});

it('emits FAQPage only for visible FAQs actually rendered on the page', function () {
    $service = Service::factory()->published()->create();
    Faq::factory()->for($service, 'faqable')->create(['question' => 'Visible question?', 'answer' => '<p>Visible answer.</p>']);
    Faq::factory()->for($service, 'faqable')->create(['question' => 'Hidden question?', 'is_visible' => false]);
    $noFaqs = Service::factory()->published()->create();

    $graph = graphFrom($this->get('/services/'.$service->slug));
    expect($graph)->toHaveKey('FAQPage')
        ->and(array_column($graph['FAQPage']['mainEntity'], 'name'))->toBe(['Visible question?'])
        ->and($graph['FAQPage']['mainEntity'][0]['acceptedAnswer']['text'])->toBe('Visible answer.');

    expect(graphFrom($this->get('/services/'.$noFaqs->slug)))->not->toHaveKey('FAQPage');
});

it('emits FAQPage for FAQs rendered through a faq block on a CMS page', function () {
    $page = Page::factory()->published()->create(['blocks' => [['type' => 'faq', 'data' => ['mode' => 'host_faqs']]]]);
    Faq::factory()->for($page, 'faqable')->create(['question' => 'Block question?']);

    expect(array_column(graphFrom($this->get('/'.$page->slug))['FAQPage']['mainEntity'], 'name'))->toBe(['Block question?']);
});

it('omits placeholder and empty values instead of inventing data', function () {
    Setting::factory()->create(['key' => 'company.description', 'value' => '[PLACEHOLDER: description]']);
    Setting::factory()->create(['key' => 'contact.address', 'value' => '']);
    $service = Service::factory()->published()->create(['short_description' => null, 'tagline' => null]);

    $graph = graphFrom($this->get('/services/'.$service->slug));

    expect($graph['Organization'])->not->toHaveKeys(['description', 'address'])
        ->and($graph['WebPage'])->not->toHaveKey('primaryImageOfPage');
});

it('applies validated overrides and schema type but protects identity keys', function () {
    $service = Service::factory()->published()->create();
    SeoMeta::factory()->for($service, 'seoable')->create(['schema_overrides' => ['areaServed' => 'India', '@id' => 'https://evil', '@type' => 'Review', 'aggregateRating' => ['ratingValue' => '<b>5</b>']]]);

    $node = graphFrom($this->get('/services/'.$service->slug))['Service'];

    expect($node['areaServed'])->toBe('India')
        ->and($node['@id'])->toBe('http://localhost/services/'.$service->slug.'#service')
        ->and($node['@type'])->toBe('Service')
        ->and($node['aggregateRating']['ratingValue'])->toBe('5');
});

it('encodes JSON-LD so script injection through CMS text is impossible', function () {
    $service = Service::factory()->published()->create(['name' => '</script><script>alert(1)</script>', 'short_description' => 'x']);

    $content = $this->get('/services/'.$service->slug)->getContent();

    expect($content)->not->toContain('</script><script>alert(1)')
        ->and($content)->toContain('</script>');

    expect(SchemaGraphBuilder::encode([['@type' => 'Thing', 'name' => '</script>']]))->not->toContain('</script>');
});

it('emits collection page schema with breadcrumbs on listing pages', function () {
    Service::factory()->published()->create();

    $graph = graphFrom($this->get('/services'));

    expect($graph)->toHaveKeys(['Organization', 'WebSite', 'CollectionPage', 'BreadcrumbList'])
        ->and($graph['CollectionPage']['url'])->toBe('http://localhost/services');
});
