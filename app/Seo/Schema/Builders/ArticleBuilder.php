<?php

namespace App\Seo\Schema\Builders;

use App\Models\Article;
use App\Seo\IndexabilityResolver;
use App\Seo\Schema\Concerns\OmitsEmptyValues;
use App\Seo\Schema\SchemaBuilder;
use App\Seo\Schema\SchemaContext;
use App\Services\Cms\PublicUrl;

class ArticleBuilder implements SchemaBuilder
{
    use OmitsEmptyValues;

    public function __construct(
        private readonly PublicUrl $urls,
        private readonly IndexabilityResolver $absolute,
    ) {}

    public function supports(SchemaContext $context): bool
    {
        return $context->entity instanceof Article;
    }

    public function build(SchemaContext $context): array
    {
        /** @var Article $article */
        $article = $context->entity;
        $author = $article->relationLoaded('author') ? $article->author : null;

        return [$this->compact([
            '@type' => 'Article',
            '@id' => $context->url.'#article',
            'headline' => $article->title,
            'description' => $this->text($article->excerpt),
            'url' => $context->url,
            'mainEntityOfPage' => ['@id' => $context->webPageId()],
            'image' => $context->meta->ogImage,
            'datePublished' => $context->meta->publishedTime,
            'dateModified' => $context->meta->modifiedTime,
            'author' => $author && $author->is_visible ? $this->compact([
                '@type' => 'Person',
                'name' => $author->name,
                'jobTitle' => $author->role_title,
                'url' => filled(strip_tags((string) $author->bio)) ? $this->absolute->absolute($this->urls->pathFor($author)) : null,
            ]) : null,
            'publisher' => ['@id' => $context->organizationId()],
            'articleSection' => $article->relationLoaded('category') ? $article->category?->name : null,
            'keywords' => $article->relationLoaded('tags') ? $article->tags->pluck('name')->all() : [],
            'inLanguage' => str_replace('_', '-', app()->getLocale()),
        ])];
    }
}
