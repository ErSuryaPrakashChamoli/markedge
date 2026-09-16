<?php

namespace App\Editorial;

use App\Cms\Blocks\BlockRegistry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\MessageBag;

/**
 * Refuses snapshots that cannot be applied safely: foreign attributes, unknown or invalid
 * blocks, malformed SEO values or schema overrides, non-integer ids.
 */
class RevisionSnapshotValidator
{
    public function __construct(private readonly BlockRegistry $blocks) {}

    public function validate(mixed $snapshot, Model $target): MessageBag
    {
        $errors = new MessageBag;

        if (! is_array($snapshot) || ! is_array($snapshot['attributes'] ?? null)) {
            return $errors->add('snapshot', 'The revision snapshot is malformed.');
        }

        $allowed = $target->revisionedAttributes();

        foreach (array_keys($snapshot['attributes']) as $key) {
            if (! is_string($key) || ! in_array($key, $allowed, true)) {
                $errors->add('attributes', "Attribute \"{$key}\" cannot be restored on this content type.");
            }
        }

        if (array_key_exists('slug', $snapshot['attributes']) && (! is_string($snapshot['attributes']['slug']) || preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $snapshot['attributes']['slug']) !== 1)) {
            $errors->add('attributes', 'The snapshot slug is not a valid slug.');
        }

        if (array_key_exists('blocks', $snapshot['attributes'])) {
            $blocks = $snapshot['attributes']['blocks'];

            if ($blocks !== null && ! is_array($blocks)) {
                $errors->add('blocks', 'The snapshot blocks are malformed.');
            } elseif (is_array($blocks)) {
                $errors->merge($this->blocks->validate($blocks, $target->getMorphClass()));
            }
        }

        $seo = $snapshot['seo'] ?? null;

        if ($seo !== null) {
            if (! is_array($seo)) {
                $errors->add('seo', 'The snapshot SEO data is malformed.');
            } else {
                foreach (array_keys($seo) as $key) {
                    if (! in_array($key, RevisionSnapshot::SEO_FIELDS, true)) {
                        $errors->add('seo', "SEO field \"{$key}\" cannot be restored.");
                    }
                }

                if (isset($seo['canonical_url']) && (! is_string($seo['canonical_url']) || (! str_starts_with($seo['canonical_url'], '/') && filter_var($seo['canonical_url'], FILTER_VALIDATE_URL) === false))) {
                    $errors->add('seo', 'The snapshot canonical URL is invalid.');
                }

                if (isset($seo['schema_overrides']) && (! is_array($seo['schema_overrides']) || array_is_list($seo['schema_overrides']))) {
                    $errors->add('seo', 'Schema overrides must be a JSON object.');
                }

                foreach (['robots_index', 'robots_follow', 'include_in_sitemap'] as $flag) {
                    if (array_key_exists($flag, $seo) && $seo[$flag] !== null && ! is_bool($seo[$flag]) && ! in_array($seo[$flag], [0, 1, '0', '1'], true)) {
                        $errors->add('seo', "SEO flag \"{$flag}\" must be true or false.");
                    }
                }
            }
        }

        foreach (['media', 'relations'] as $group) {
            $value = $snapshot[$group] ?? [];

            if (! is_array($value)) {
                $errors->add($group, "The snapshot {$group} data is malformed.");

                continue;
            }

            foreach ($value as $name => $ids) {
                if (! is_string($name) || ! is_array($ids) || array_filter($ids, fn ($id) => ! is_int($id) || $id < 1) !== []) {
                    $errors->add($group, "The snapshot {$group} entry \"{$name}\" is malformed.");
                }
            }
        }

        return $errors;
    }
}
