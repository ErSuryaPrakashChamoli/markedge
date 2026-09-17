<?php

namespace Database\Seeders;

use App\Enums\MenuItemType;
use App\Enums\PublishStatus;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\Cta;
use App\Models\Form;
use App\Models\Industry;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Product;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Solution;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

/**
 * Applies the Phase 12 content library (database/content/*.php) to the existing CMS records.
 *
 * Governance: publication status is never changed here. Existing records keep their status; new
 * article drafts are created as Draft without an author. Publishing goes through the Phase 9
 * workflow (review → approve → publish). Safe to re-run: copy, SEO rows, FAQs and relations are
 * replaced from the library, revisions record each change.
 */
class Phase12ContentSeeder extends Seeder
{
    public function run(): void
    {
        $this->categories();
        $this->services();
        $this->products();
        $this->solutions();
        $this->industries();
        $this->pages();
        $this->articles();
        $this->menus();
    }

    /**
     * Footer "Get in touch" column so conversion pages are linked directly, not only via CTA redirects;
     * links to pages that have no approved content yet are hidden until they are published.
     */
    protected function menus(): void
    {
        $footer = Menu::query()->where('key', 'footer')->first();

        if ($footer !== null && ! $footer->items()->where('label', 'Get in touch')->exists()) {
            $column = $footer->items()->create(['label' => 'Get in touch', 'type' => MenuItemType::Heading, 'sort_order' => 1]);

            foreach ([['Contact', '/contact'], ['Request a quote', '/request-quote'], ['Request a consultation', '/request-consultation'], ['Request a product demo', '/request-demo'], ['Request an IT assessment', '/request-it-assessment'], ['Request a digital growth audit', '/request-digital-growth-audit']] as $index => [$label, $url]) {
                $footer->items()->create(['parent_id' => $column->id, 'label' => $label, 'type' => MenuItemType::Url, 'url' => $url, 'sort_order' => $index]);
            }
        }

        // URL menu items follow the publication state of the page or product they point to, so the
        // footer never links to a 404; re-running the seeder after publishing re-enables them.
        foreach (MenuItem::query()->whereNotNull('url')->where('url', 'like', '/%')->get() as $item) {
            $path = trim((string) parse_url($item->url, PHP_URL_PATH), '/');
            $target = str_starts_with($path, 'products/')
                ? Product::query()->where('slug', substr($path, 9))->first()
                : (str_contains($path, '/') ? null : Page::query()->where('slug', $path)->first());

            if ($target === null) {
                continue;
            }

            $visible = method_exists($target, 'isPubliclyVisible') ? $target->isPubliclyVisible() : $target->isPublished();
            $item->update(['is_visible' => $visible]);
        }
    }

    protected function categories(): void
    {
        foreach ($this->library('categories') as $slug => $content) {
            $category = ServiceCategory::query()->where('slug', $slug)->first();

            if ($category === null) {
                continue;
            }

            $category->update(['tagline' => $content['tagline'], 'short_description' => $content['short_description'], 'description' => $content['description']]);
            $this->seo($category, $content['seo']);
        }
    }

    protected function services(): void
    {
        $library = $this->library('services-build') + $this->library('services-operate') + $this->library('services-grow');

        foreach ($library as $slug => $content) {
            $service = Service::query()->where('slug', $slug)->first();

            if ($service === null) {
                continue;
            }

            $service->update([
                'tagline' => $content['tagline'],
                'short_description' => $content['short_description'],
                'overview' => $content['overview'],
                'benefits' => $content['benefits'],
                'features' => $content['features'],
                'process' => $content['process'],
                'deliverables' => $content['deliverables'],
            ]);

            $this->seo($service, $content['seo']);
            $this->faqs($service, $content['faqs']);
            $this->sync($service, 'solutions', Solution::class, $content['related']['solutions'] ?? []);
            $this->sync($service, 'industries', Industry::class, $content['related']['industries'] ?? []);
            $this->sync($service, 'products', Product::class, $content['related']['products'] ?? []);
        }
    }

    protected function products(): void
    {
        foreach ($this->library('products') as $slug => $content) {
            $product = Product::query()->where('slug', $slug)->first();

            if ($product === null) {
                continue;
            }

            $product->update([
                'tagline' => $content['tagline'],
                'short_description' => $content['short_description'],
                'long_description' => $content['long_description'],
                'benefits' => $content['benefits'],
                'use_cases' => $content['use_cases'],
                'demo_form_id' => Form::query()->where('key', 'product-demo')->value('id'),
            ]);

            $this->seo($product, $content['seo']);
            $this->faqs($product, $content['faqs']);
            $this->sync($product, 'solutions', Solution::class, $content['related']['solutions'] ?? []);
            $this->sync($product, 'industries', Industry::class, $content['related']['industries'] ?? []);
            $this->sync($product, 'services', Service::class, $content['related']['services'] ?? []);
            $this->productPlatform($product);
        }
    }

    /**
     * Modules → features → capabilities, deployment and security statements and starter documentation
     * from database/content/product-platform.php. Matched by name/slug so re-running updates in place;
     * admin edits to other fields and extra records are left alone. Document status is never changed
     * once a document exists.
     */
    protected function productPlatform(Product $product): void
    {
        $content = $this->library('product-platform')[$product->slug] ?? null;

        if ($content === null) {
            return;
        }

        $product->update([
            'product_type' => $content['product_type'] ?? $product->product_type,
            'long_description' => $content['long_description'] ?? $product->long_description,
            'deployment' => $content['deployment'] ?? $product->deployment,
            'security' => $content['security'] ?? $product->security,
        ]);

        foreach ($content['modules'] ?? [] as $moduleIndex => $moduleContent) {
            $module = $product->modules()->updateOrCreate(['name' => $moduleContent['name']], [
                'summary' => $moduleContent['summary'] ?? null,
                'highlights' => $moduleContent['highlights'] ?? null,
                'sort_order' => $moduleIndex,
            ]);

            foreach ($moduleContent['features'] ?? [] as $featureIndex => $featureContent) {
                $feature = $product->features()->updateOrCreate(['title' => $featureContent['title']], [
                    'product_module_id' => $module->id,
                    'description' => $featureContent['description'] ?? null,
                    'sort_order' => $featureIndex,
                ]);

                foreach ($featureContent['capabilities'] ?? [] as $capabilityIndex => $capability) {
                    $feature->capabilities()->updateOrCreate(['name' => $capability['name']], [
                        'description' => $capability['description'] ?? null,
                        'sort_order' => $capabilityIndex,
                    ]);
                }
            }
        }

        foreach ($content['documents'] ?? [] as $index => $document) {
            $existing = $product->documents()->where('slug', $document['slug'])->first();

            $attributes = [
                'title' => $document['title'],
                'section' => $document['section'] ?? null,
                'excerpt' => $document['excerpt'] ?? null,
                'body' => $document['body'] ?? null,
                'sort_order' => $index,
            ];

            if ($existing) {
                $existing->update($attributes);
            } else {
                $product->documents()->create($attributes + ['slug' => $document['slug'], 'status' => PublishStatus::Published, 'published_at' => now()]);
            }
        }
    }

    protected function solutions(): void
    {
        foreach ($this->library('solutions') as $slug => $content) {
            $solution = Solution::query()->where('slug', $slug)->first();

            if ($solution === null) {
                continue;
            }

            $solution->update([
                'tagline' => $content['tagline'],
                'short_description' => $content['short_description'],
                'problem_statement' => $content['problem_statement'],
                'approach' => $content['approach'],
                'outcomes' => $content['outcomes'],
            ]);

            $this->seo($solution, $content['seo']);
            $this->sync($solution, 'services', Service::class, $content['related']['services'] ?? []);
            $this->sync($solution, 'industries', Industry::class, $content['related']['industries'] ?? []);
            $this->sync($solution, 'products', Product::class, $content['related']['products'] ?? []);
        }
    }

    protected function industries(): void
    {
        foreach ($this->library('industries') as $slug => $content) {
            $industry = Industry::query()->where('slug', $slug)->first();

            if ($industry === null) {
                continue;
            }

            $industry->update([
                'tagline' => $content['tagline'],
                'short_description' => $content['short_description'],
                'description' => $content['description'],
                'challenges' => $content['challenges'],
                'is_featured' => $content['priority'],
            ]);

            $this->seo($industry, $content['seo']);
            $this->faqs($industry, $content['faqs'] ?? []);
            $this->sync($industry, 'services', Service::class, $content['related']['services'] ?? []);
            $this->sync($industry, 'solutions', Solution::class, $content['related']['solutions'] ?? []);
            $this->sync($industry, 'products', Product::class, $content['related']['products'] ?? []);
        }
    }

    protected function pages(): void
    {
        foreach ($this->library('pages') as $slug => $content) {
            $page = Page::query()->where('slug', $slug)->first();

            if ($page === null) {
                continue;
            }

            $attributes = ['excerpt' => $content['excerpt']];

            if (isset($content['template'])) {
                $attributes['template'] = $content['template'];
            }

            if (($content['review_only'] ?? false) && $page->status === PublishStatus::Draft) {
                $attributes['status'] = PublishStatus::Review;
            }

            if (isset($content['form_key'])) {
                $form = Form::query()->where('key', $content['form_key'])->first();
                $attributes['form_id'] = $form?->id;

                if ($form && isset($content['form'])) {
                    $form->update($content['form']);
                }
            }

            if (isset($content['blocks'])) {
                $attributes['blocks'] = array_map(fn (array $block): array => $this->block($block), $content['blocks']);
            }

            if (isset($content['replacements'])) {
                $attributes['blocks'] = $this->replace($page->blocks ?? [], $content['replacements']);
            }

            $page->update($attributes);
            $this->seo($page, $content['seo']);
        }
    }

    protected function articles(): void
    {
        foreach ($this->library('articles') as $slug => $content) {
            $category = ArticleCategory::query()->where('slug', $content['category'])->first();
            $cta = Cta::query()->where('key', $content['cta'])->first();

            $article = Article::query()->withTrashed()->firstOrNew(['slug' => $slug]);
            $article->fill([
                'title' => $content['title'],
                'excerpt' => $content['excerpt'],
                'body' => $content['body'],
                'article_category_id' => $category?->id,
                'cta_id' => $cta?->id,
            ]);

            if (! $article->exists) {
                $article->status = PublishStatus::Draft;
            }

            $article->save();
            $this->seo($article, $content['seo']);
            $this->sync($article, 'services', Service::class, $content['related_services'] ?? []);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function library(string $name): array
    {
        return require database_path("content/{$name}.php");
    }

    /**
     * @param  array{title: string, description: string}  $seo
     */
    protected function seo(Model $record, array $seo): void
    {
        $record->seo()->updateOrCreate([], ['title' => $seo['title'], 'description' => $seo['description']]);
    }

    /**
     * Replaces the host's FAQs with the library set, keeping order deterministic.
     *
     * @param  array<int, array{question: string, answer: string}>  $faqs
     */
    protected function faqs(Model $record, array $faqs): void
    {
        if (! method_exists($record, 'faqs')) {
            return;
        }

        $record->faqs()->delete();

        foreach ($faqs as $index => $faq) {
            $record->faqs()->create(['question' => $faq['question'], 'answer' => $faq['answer'], 'is_visible' => true, 'sort_order' => $index]);
        }
    }

    /**
     * @param  class-string<Model>  $related
     * @param  array<int, string>  $slugs
     */
    protected function sync(Model $record, string $relation, string $related, array $slugs): void
    {
        if (! method_exists($record, $relation)) {
            return;
        }

        $ids = $related::query()->whereIn('slug', $slugs)->pluck('id')->all();
        $record->{$relation}()->sync($ids);
    }

    /**
     * Resolves CTA and form keys to ids so the library stays environment-independent.
     *
     * @param  array{type: string, data: array<string, mixed>}  $block
     * @return array{type: string, data: array<string, mixed>}
     */
    protected function block(array $block): array
    {
        $data = ['is_enabled' => true, 'theme' => 'light'] + $block['data'];

        if (isset($data['cta_key'])) {
            $data['cta_id'] = Cta::query()->where('key', $data['cta_key'])->value('id');
            unset($data['cta_key']);
        }

        if (isset($data['form_key'])) {
            $data['form_id'] = Form::query()->where('key', $data['form_key'])->value('id');
            unset($data['form_key']);
        }

        return ['type' => $block['type'], 'data' => $data];
    }

    /**
     * Replaces specific data keys on the first block of each type ("type.key" => value).
     *
     * @param  array<int, array<string, mixed>>  $blocks
     * @param  array<string, mixed>  $replacements
     * @return array<int, array<string, mixed>>
     */
    protected function replace(array $blocks, array $replacements): array
    {
        foreach ($replacements as $path => $value) {
            [$type, $key] = explode('.', $path, 2);

            foreach ($blocks as $index => $block) {
                if (($block['type'] ?? null) === $type) {
                    $blocks[$index]['data'][$key] = $value;

                    break;
                }
            }
        }

        return $blocks;
    }
}
