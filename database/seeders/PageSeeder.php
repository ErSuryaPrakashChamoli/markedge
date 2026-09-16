<?php

namespace Database\Seeders;

use App\Enums\PageTemplate;
use App\Enums\PublishStatus;
use App\Models\Cta;
use App\Models\Form;
use App\Models\Page;
use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;

/**
 * Structural pages as drafts. The home page carries the block narrative from the
 * brief; every body text is a visible placeholder until real copy is supplied.
 */
class PageSeeder extends Seeder
{
    public function run(): void
    {
        $this->page('home', 'Home', PageTemplate::Home, $this->homeBlocks());
        $this->page('about', 'About Markedge', PageTemplate::About, $this->placeholderBlocks('About Markedge'));
        $this->page('careers', 'Careers', PageTemplate::Careers, $this->placeholderBlocks('Careers at Markedge'));
        $this->page('contact', 'Contact', PageTemplate::Contact, [], form: 'general-enquiry');

        foreach ([
            'request-consultation' => ['Request a Consultation', 'consultation'],
            'request-demo' => ['Request a Demo', 'product-demo'],
            'request-quote' => ['Request a Quote', 'quote-request'],
            'request-it-assessment' => ['Request an IT Assessment', 'it-assessment'],
            'request-digital-growth-audit' => ['Request a Digital Growth Audit', 'digital-growth-audit'],
        ] as $slug => [$title, $formKey]) {
            $this->page($slug, $title, PageTemplate::Form, [], form: $formKey);
        }

        foreach (['privacy-policy' => 'Privacy Policy', 'terms' => 'Terms of Service', 'cookie-policy' => 'Cookie Policy'] as $slug => $title) {
            $this->page($slug, $title, PageTemplate::Legal, $this->placeholderBlocks($title));
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $blocks
     */
    protected function page(string $slug, string $title, PageTemplate $template, array $blocks, ?string $form = null): void
    {
        $page = Page::query()->withTrashed()->firstOrNew(['slug' => $slug]);

        if ($page->exists) {
            return;
        }

        $page->fill([
            'title' => $title,
            'template' => $template,
            'blocks' => $blocks,
            'form_id' => $form ? Form::query()->where('key', $form)->value('id') : null,
            'cta_id' => Cta::query()->where('key', 'start-conversation')->value('id'),
            'status' => PublishStatus::Draft,
        ])->save();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function placeholderBlocks(string $heading): array
    {
        return [
            $this->block('rich_text', ['body' => "<h2>{$heading}</h2><p>[PLACEHOLDER: {$heading} content]</p>", 'width' => 'narrow']),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function homeBlocks(): array
    {
        $categories = ServiceCategory::query()->ordered()->pluck('id', 'slug');
        $cta = fn (string $key): ?int => Cta::query()->where('key', $key)->value('id');

        return [
            $this->block('hero', [
                'eyebrow' => 'Build. Operate. Grow.',
                'headline' => 'Technology that moves business forward.',
                'subheading' => '[PLACEHOLDER: supporting message covering software, IT infrastructure, digital growth and business products]',
                'primary_cta_id' => $cta('start-conversation'),
                'secondary_label' => 'Explore Our Capabilities',
                'secondary_url' => '/services',
                'variant' => 'ecosystem',
                'theme' => 'dark',
            ]),
            $this->block('capability_intro', [
                'heading' => 'One technology partner. Multiple capabilities.',
                'show_products' => true,
            ]),
            $this->block('split_content', [
                'heading' => 'Technology is not one function of a business. It is the foundation of how a business operates.',
                'body' => '<p>[PLACEHOLDER: how Markedge brings technology, infrastructure and digital growth together]</p>',
                'media_position' => 'right',
            ]),
            $this->block('service_grid', [
                'heading' => 'Build what your business needs.',
                'service_category_id' => $categories['technology'] ?? null,
                'cta_label' => 'Explore Technology Services',
                'limit' => 6,
            ]),
            $this->block('service_grid', [
                'heading' => 'Keep technology working.',
                'service_category_id' => $categories['it-infrastructure'] ?? null,
                'cta_label' => 'Explore IT Infrastructure',
                'limit' => 6,
                'theme' => 'dark',
            ]),
            $this->block('service_grid', [
                'heading' => 'Turn digital presence into business growth.',
                'service_category_id' => $categories['digital-growth'] ?? null,
                'cta_label' => 'Explore Digital Growth',
                'limit' => 6,
            ]),
            $this->block('solution_grid', ['heading' => 'What are you trying to solve?', 'mode' => 'featured']),
            $this->block('product_showcase', ['heading' => 'Markedge Products', 'mode' => 'all_active', 'cta_label' => 'Explore Products']),
            $this->block('industry_grid', ['heading' => 'Technology for businesses across industries.', 'mode' => 'featured']),
            $this->block('technology_grid', ['heading' => 'Engineering & Technology', 'mode' => 'all', 'display' => 'logos']),
            $this->block('process', [
                'heading' => 'How We Work',
                'steps' => array_map(
                    fn (string $title): array => ['title' => $title, 'text' => "[PLACEHOLDER: {$title} description]"],
                    ['Discover', 'Define', 'Design', 'Build', 'Deploy', 'Operate', 'Grow'],
                ),
                'orientation' => 'horizontal',
                'theme' => 'dark',
            ]),
            $this->block('case_study_grid', ['heading' => 'Selected Work', 'mode' => 'latest', 'limit' => 3]),
            $this->block('feature_grid', [
                'heading' => 'More than a technology vendor.',
                'columns' => 3,
                'items' => array_map(
                    fn (string $title): array => ['title' => $title, 'text' => "[PLACEHOLDER: {$title}]"],
                    ['Business-first technology', 'Multi-disciplinary capability', 'Built for evolution', 'Product mindset', 'Long-term partnership', 'Performance-focused'],
                ),
            ]),
            $this->block('article_grid', ['heading' => 'Insights', 'mode' => 'latest', 'limit' => 3]),
            $this->block('cta', ['cta_id' => $cta('start-conversation'), 'theme' => 'dark']),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function block(string $type, array $data): array
    {
        return ['type' => $type, 'data' => ['is_enabled' => true, 'theme' => 'light'] + $data];
    }
}
