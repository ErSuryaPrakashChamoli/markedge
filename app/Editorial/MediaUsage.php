<?php

namespace App\Editorial;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Schema;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Factual media usage: every file belongs to one owner record; a file whose owner is gone
 * (deleted or soft-deleted) is "unused". Nothing is deleted automatically.
 */
class MediaUsage
{
    /**
     * @param  Builder<Media>  $query
     * @return Builder<Media>
     */
    public static function orphaned(Builder $query): Builder
    {
        return $query->where(function (Builder $outer): void {
            $first = true;

            foreach (Relation::morphMap() as $alias => $class) {
                $table = (new $class)->getTable();
                $softDeletes = Schema::hasColumn($table, 'deleted_at');
                $method = $first ? 'where' : 'orWhere';
                $first = false;

                $outer->{$method}(function (Builder $q) use ($alias, $table, $softDeletes): void {
                    $q->where('media.model_type', $alias)->whereNotExists(function ($exists) use ($table, $softDeletes): void {
                        $exists->selectRaw('1')->from($table)->whereColumn("{$table}.id", 'media.model_id');

                        if ($softDeletes) {
                            $exists->whereNull("{$table}.deleted_at");
                        }
                    });
                });
            }
        });
    }

    public static function label(Media $media): string
    {
        $owner = $media->model;
        $type = ucfirst(str_replace('_', ' ', $media->model_type));

        if ($owner === null) {
            return "{$type} #{$media->model_id} (missing, unused)";
        }

        $title = WorkflowModels::titleOf($owner);
        $state = match (true) {
            method_exists($owner, 'isPubliclyVisible') => $owner->isPubliclyVisible() ? 'published' : 'not public',
            method_exists($owner, 'isPublished') => $owner->isPublished() ? 'published' : 'not public',
            default => 'in use',
        };

        return "{$type}: {$title} ({$state})";
    }
}
