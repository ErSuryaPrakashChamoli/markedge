<?php

namespace Database\Seeders;

use App\Enums\MenuItemType;
use App\Enums\PageTemplate;
use App\Enums\PublishStatus;
use App\Models\Cta;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * The Resources section: the /resources page (blogs, news, social videos), the Resources teaser on the
 * home page and the Resources menu entries. Safe on an existing site: it only adds what is missing and
 * only rewrites menu items and home blocks that still carry their seeded values, so admin edits are kept.
 *
 * Existing sites: php artisan db:seed --class=ResourcesSeeder --force
 */
class ResourcesSeeder extends Seeder
{
    public const string SLUG = 'resources';

    public function run(): void
    {
        $this->resourcesPage();
        $this->homeTeaser();
        $this->headerMenu();
        $this->footerMenu();
    }

    /**
     * Created with the home page's status, so a live site gets a live Resources page.
     */
    protected function resourcesPage(): void
    {
        if (Page::query()->withTrashed()->where('slug', self::SLUG)->exists()) {
            return;
        }

        $live = Page::query()->where('slug', Page::HOME_SLUG)->first()?->isPublished() ?? false;

        $page = new Page([
            'title' => 'Resources',
            'template' => PageTemplate::Default,
            'excerpt' => 'Blogs, news and videos from the Markedge team on technology, IT infrastructure and digital growth.',
            'blocks' => [
                $this->block('article_grid', ['heading' => 'Blogs', 'intro' => 'Guides and opinions from the people who build, run and grow technology for our clients.', 'mode' => 'latest', 'article_type' => 'blog', 'limit' => 6, 'anchor' => 'blogs']),
                $this->block('article_grid', ['heading' => 'News', 'intro' => 'Company announcements and updates from the industries we work in.', 'mode' => 'latest', 'article_type' => 'news', 'limit' => 6, 'anchor' => 'news', 'theme' => 'neutral']),
                $this->block('social_videos', ['heading' => 'Videos', 'intro' => 'Short videos we share on Instagram, LinkedIn, YouTube and other platforms.', 'mode' => 'latest', 'limit' => 12, 'anchor' => 'videos']),
            ],
            'cta_id' => Cta::query()->where('key', 'start-conversation')->value('id'),
            'status' => $live ? PublishStatus::Published : PublishStatus::Draft,
            'published_at' => $live ? now() : null,
        ]);
        $page->slug = self::SLUG;
        $page->save();
    }

    /**
     * Swaps the seeded "Insights" article grid on the home page for the Resources teaser, or places the
     * teaser before the closing call to action when that grid is gone.
     */
    protected function homeTeaser(): void
    {
        $home = Page::query()->where('slug', Page::HOME_SLUG)->first();

        if ($home === null) {
            return;
        }

        $blocks = array_values($home->blocks ?? []);

        if (collect($blocks)->contains(fn (mixed $block): bool => ($block['type'] ?? null) === 'resources')) {
            return;
        }

        $teaser = $this->block('resources', [
            'heading' => 'Learn from our latest work',
            'intro' => 'Blogs, news and videos on technology, IT infrastructure and digital growth.',
            'show_blog' => true, 'show_news' => true, 'show_videos' => true,
            'limit' => 3,
            'more_label' => 'Explore all resources',
            'more_url' => '/'.self::SLUG,
        ]);

        $seededGrid = collect($blocks)->search(fn (mixed $block): bool => ($block['type'] ?? null) === 'article_grid'
            && ($block['data']['heading'] ?? null) === 'Insights'
            && ($block['data']['mode'] ?? null) === 'latest');

        if ($seededGrid !== false) {
            $blocks[$seededGrid] = $teaser;
        } else {
            $lastCta = collect($blocks)->keys()->last(fn (int $index): bool => ($blocks[$index]['type'] ?? null) === 'cta');
            array_splice($blocks, $lastCta ?? count($blocks), 0, [$teaser]);
        }

        $home->forceFill(['blocks' => $blocks])->save();
    }

    protected function headerMenu(): void
    {
        $menu = Menu::query()->where('key', 'header')->first();
        $item = $menu?->items()->whereNull('parent_id')->where('label', 'Insights')->where('url', '/insights')->first();

        if ($item === null) {
            return;
        }

        $item->forceFill(['label' => 'Resources', 'url' => '/'.self::SLUG, 'settings' => null])->save();

        foreach ([['Blogs', '/insights?type=blog'], ['News', '/insights?type=news'], ['Videos', '/'.self::SLUG.'#videos'], ['All Articles', '/insights']] as $index => [$label, $url]) {
            $child = $menu->items()->firstOrNew(['parent_id' => $item->id, 'url' => $url], ['label' => $label, 'type' => MenuItemType::Url]);
            $child->forceFill(['sort_order' => $index])->save();
        }
    }

    protected function footerMenu(): void
    {
        $menu = Menu::query()->where('key', 'footer')->first();

        $menu?->items()->whereNotNull('parent_id')->where('label', 'Insights')->where('url', '/insights')
            ->each(fn (MenuItem $item) => $item->forceFill(['label' => 'Resources', 'url' => '/'.self::SLUG])->save());
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function block(string $type, array $data): array
    {
        return ['type' => $type, 'data' => $data + ['is_enabled' => true, 'theme' => 'light']];
    }
}
