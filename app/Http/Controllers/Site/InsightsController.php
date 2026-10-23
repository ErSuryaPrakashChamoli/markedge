<?php

namespace App\Http\Controllers\Site;

use App\Enums\ArticleType;
use App\Http\Controllers\Controller;
use App\Seo\SeoEngine;
use App\Services\Cms\ContentResolver;
use App\Services\Cms\CtaResolver;
use App\Services\Cms\PageRenderer;
use App\Services\Cms\PublicUrl;
use Illuminate\Contracts\View\View;

class InsightsController extends Controller
{
    /**
     * The full article listing; ?type=blog or ?type=news narrows it to one kind (noindex, the unfiltered
     * listing stays canonical).
     */
    public function index(ContentResolver $content, SeoEngine $meta, CtaResolver $ctas): View
    {
        $type = ArticleType::tryFrom((string) request()->query('type'));

        return $this->archive($content, $ctas, [
            'meta' => $type
                ? $meta->forListing($type->pluralLabel(), null, '/insights', indexable: false)
                : $meta->forListing('Insights', 'Articles on technology, infrastructure and digital growth from Markedge Technologies.', '/insights', breadcrumbs: [['label' => 'Insights', 'url' => null]]),
            'articles' => $content->articles(type: $type),
            'featured' => request()->integer('page', 1) === 1 ? $content->featuredArticles(type: $type) : collect(),
            'heading' => $type?->pluralLabel() ?? 'Insights',
            'intro' => null,
            'type' => $type,
            'breadcrumbs' => $type ? [['label' => 'Insights', 'url' => '/insights'], ['label' => $type->pluralLabel(), 'url' => null]] : [],
        ]);
    }

    public function category(ContentResolver $content, SeoEngine $meta, CtaResolver $ctas, PublicUrl $urls, string $slug): View
    {
        $category = $content->articleCategory($slug) ?? abort(404);

        return $this->archive($content, $ctas, [
            'meta' => $meta->forEntity($category, [['label' => 'Insights', 'url' => '/insights'], ['label' => $category->name, 'url' => null]])->with(['title' => $meta->withSuffix($category->name.' articles')]),
            'articles' => $content->articles(category: $category),
            'heading' => $category->name,
            'intro' => $category->description,
            'current' => $category,
            'breadcrumbs' => [['label' => 'Insights', 'url' => '/insights'], ['label' => $category->name, 'url' => null]],
        ]);
    }

    public function tag(ContentResolver $content, SeoEngine $meta, CtaResolver $ctas, string $slug): View
    {
        $tag = $content->tag($slug) ?? abort(404);

        return $this->archive($content, $ctas, [
            'meta' => $meta->forListing('Articles tagged '.$tag->name, null, '/insights/tag/'.$tag->slug, indexable: false),
            'articles' => $content->articles(tag: $tag),
            'heading' => 'Tagged: '.$tag->name,
            'intro' => null,
            'breadcrumbs' => [['label' => 'Insights', 'url' => '/insights'], ['label' => $tag->name, 'url' => null]],
        ]);
    }

    public function author(ContentResolver $content, SeoEngine $meta, CtaResolver $ctas, string $slug): View
    {
        $author = $content->author($slug) ?? abort(404);

        return $this->archive($content, $ctas, [
            'meta' => $meta->forEntity($author, [['label' => 'Insights', 'url' => '/insights'], ['label' => $author->name, 'url' => null]]),
            'articles' => $content->articles(author: $author),
            'heading' => $author->name,
            'intro' => $author->role_title,
            'author' => $author,
            'breadcrumbs' => [['label' => 'Insights', 'url' => '/insights'], ['label' => $author->name, 'url' => null]],
        ]);
    }

    public function show(ContentResolver $content, PageRenderer $renderer, string $slug): View
    {
        return $renderer->render($content->article($slug) ?? abort(404));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function archive(ContentResolver $content, CtaResolver $ctas, array $data): View
    {
        return view('pages.insights.index', $data + [
            'categories' => $content->articleCategories(),
            'featured' => collect(),
            'breadcrumbs' => [],
            'current' => null,
            'author' => null,
            'type' => null,
            'types' => ArticleType::cases(),
            'cta' => $ctas->fromSetting('cta.default_article') ?? $ctas->fromSetting('cta.default'),
        ]);
    }
}
