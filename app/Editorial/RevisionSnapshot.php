<?php

namespace App\Editorial;

use App\Models\SeoMeta;
use BackedEnum;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Builds the JSON snapshot stored on a revision: content attributes, SEO row, media ids per
 * collection and related ids. Media binaries are never copied; ids reference the library.
 */
class RevisionSnapshot
{
    /** @var array<int, string> */
    public const array RELATIONS = ['tags', 'services', 'products', 'industries', 'solutions', 'technologies', 'relatedServices', 'relatedArticles', 'caseStudies', 'faqs'];

    /** @var array<int, string> */
    public const array SEO_FIELDS = [
        'title', 'description', 'canonical_url', 'robots_index', 'robots_follow', 'og_title', 'og_description',
        'twitter_title', 'twitter_description', 'schema_type', 'schema_overrides', 'include_in_sitemap', 'sitemap_priority', 'sitemap_changefreq',
    ];

    /**
     * @return array{attributes: array<string, mixed>, seo: array<string, mixed>|null, media: array<string, array<int, int>>, relations: array<string, array<int, int>>}
     */
    public function build(Model $record): array
    {
        $attributes = [];
        $loaded = $record->getAttributes();

        // Only attributes the model actually holds: a freshly created model has not loaded database
        // defaults yet, and restoring an absent value as null would violate NOT NULL columns.
        foreach ($record->revisionedAttributes() as $key) {
            if (array_key_exists($key, $loaded)) {
                $attributes[$key] = $this->normalise($record->getAttribute($key));
            }
        }

        $seo = method_exists($record, 'seo') ? $record->seo()->first() : null;

        $media = [];

        if (method_exists($record, 'media')) {
            foreach ($record->media()->orderBy('order_column')->get(['id', 'collection_name']) as $item) {
                $media[$item->collection_name][] = (int) $item->id;
            }
        }

        $relations = [];

        foreach (self::RELATIONS as $relation) {
            if (! method_exists($record, $relation)) {
                continue;
            }

            $query = $record->{$relation}();

            if ($query instanceof BelongsToMany) {
                $relations[$relation] = $query->pluck($query->getRelated()->getQualifiedKeyName())->map(fn ($id) => (int) $id)->all();
            }
        }

        return [
            'attributes' => $attributes,
            'seo' => $seo instanceof SeoMeta ? collect($seo->only(self::SEO_FIELDS))->map(fn ($value) => $this->normalise($value))->all() : null,
            'media' => $media,
            'relations' => $relations,
        ];
    }

    /**
     * @param  array<string, mixed>  $snapshot
     */
    public function checksum(array $snapshot): string
    {
        return hash('sha256', json_encode($snapshot, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
    }

    protected function normalise(mixed $value): mixed
    {
        return match (true) {
            $value instanceof BackedEnum => $value->value,
            $value instanceof DateTimeInterface => $value->format(DATE_ATOM),
            default => $value,
        };
    }
}
