<?php

namespace App\Editorial;

use App\Events\Content\ContentVersionRestored;
use App\Models\ContentRevision;
use App\Services\Cms\Publisher;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Creates and restores content revisions. Version numbers are allocated under a row lock so
 * concurrent edits never collide; restoring never rewrites history, it appends a new version.
 */
class RevisionManager
{
    private bool $suspended = false;

    public function __construct(
        private readonly RevisionSnapshot $snapshots,
        private readonly RevisionSnapshotValidator $validator,
    ) {}

    /**
     * Records the current state as a new version when the content actually changed. Consecutive
     * saves by the same user within the coalesce window fold into the latest version so a single
     * form submission (record + SEO + media) yields one revision.
     */
    public function capture(Model $record, ?string $reason = null, bool $force = false): ?ContentRevision
    {
        if (! $record->exists || $this->suspended) {
            return null;
        }

        $snapshot = $this->snapshots->build($record);
        $checksum = $this->snapshots->checksum($snapshot);

        return DB::transaction(function () use ($record, $snapshot, $checksum, $reason, $force): ?ContentRevision {
            $latest = $this->latestQuery($record)->lockForUpdate()->first();

            if ($latest !== null && $latest->checksum === $checksum && ! $force) {
                return null;
            }

            $window = (int) config('markedge.editorial.revision_coalesce_seconds', 20);

            if (
                ! $force
                && $latest !== null
                && $latest->reason === null
                && $latest->created_by === auth()->id()
                && $latest->created_at->greaterThanOrEqualTo(now()->subSeconds($window))
            ) {
                $latest->forceFill(['snapshot' => $snapshot, 'checksum' => $checksum])->save();

                return $latest;
            }

            $revision = ContentRevision::query()->create([
                'revisionable_type' => $record->getMorphClass(),
                'revisionable_id' => $record->getKey(),
                'version' => ($latest?->version ?? 0) + 1,
                'snapshot' => $snapshot,
                'checksum' => $checksum,
                'reason' => $reason,
                'created_by' => auth()->id(),
                'created_at' => now(),
            ]);

            $this->prune($record);

            return $revision;
        });
    }

    public function latestVersion(Model $record): int
    {
        return (int) $this->latestQuery($record)->value('version');
    }

    /**
     * Applies an earlier version to the record and appends it as a new version. The record is
     * returned to Draft when it was live so nothing changes publicly until republished.
     *
     * @throws ValidationException
     */
    public function restore(ContentRevision $revision, Model $record, int $expectedLatestVersion): ContentRevision
    {
        if (auth()->check() && ! Gate::allows('update', $record)) {
            throw new AuthorizationException('You are not allowed to restore versions of this content.');
        }

        if ($revision->revisionable_type !== $record->getMorphClass() || (int) $revision->revisionable_id !== (int) $record->getKey()) {
            throw ValidationException::withMessages(['revision' => 'This version does not belong to this content.']);
        }

        $errors = $this->validator->validate($revision->snapshot, $record);

        if ($errors->isNotEmpty()) {
            throw ValidationException::withMessages($errors->toArray());
        }

        $this->suspended = true;

        try {
            return DB::transaction(function () use ($revision, $record, $expectedLatestVersion): ContentRevision {
                return $this->apply($revision, $record, $expectedLatestVersion);
            });
        } finally {
            $this->suspended = false;
        }
    }

    protected function apply(ContentRevision $revision, Model $record, int $expectedLatestVersion): ContentRevision
    {
        $latest = $this->latestQuery($record)->lockForUpdate()->value('version');

        if ((int) $latest !== $expectedLatestVersion) {
            throw ValidationException::withMessages(['revision' => "Newer edits exist (current version is v{$latest}). Reload and compare before restoring."]);
        }

        $snapshot = $revision->snapshot;
        $wasLive = in_array($record->status?->value, ['published', 'scheduled', 'review'], true);

        // Content first while the record keeps its state, so a slug change still creates its redirect.
        $record->forceFill($snapshot['attributes'])->save();

        if (is_array($snapshot['seo'] ?? null) && method_exists($record, 'seo')) {
            $record->seo()->updateOrCreate([], $snapshot['seo']);
        }

        foreach ($snapshot['relations'] ?? [] as $relation => $ids) {
            if (method_exists($record, $relation) && ($query = $record->{$relation}()) instanceof BelongsToMany) {
                $existing = $query->getRelated()->newQuery()->whereKey($ids)->pluck($query->getRelated()->getKeyName())->all();
                $query->sync(array_values(array_intersect($ids, $existing)));
            }
        }

        foreach ($snapshot['media'] ?? [] as $collection => $ids) {
            $owned = Media::query()->whereKey($ids)->where('model_type', $record->getMorphClass())->where('model_id', $record->getKey())->pluck('id')->all();

            if ($owned !== []) {
                Media::setNewOrder(array_values(array_intersect($ids, $owned)));
            }
        }

        if ($wasLive) {
            app(Publisher::class)->unpublish($record->fresh(), 'unpublished (version restored)');
        }

        $record->refresh();
        $this->suspended = false;
        $new = $this->capture($record, 'Restored from v'.$revision->version, force: true);
        $this->suspended = true;

        activity('content')
            ->performedOn($record)
            ->causedBy(auth()->user())
            ->withProperties(['from_version' => $revision->version, 'new_version' => $new?->version])
            ->event('version restored')
            ->log('Version restored');

        event(ContentVersionRestored::for($record, ['from_version' => $revision->version, 'new_version' => $new?->version]));

        return $new;
    }

    protected function prune(Model $record): void
    {
        $keep = (int) config('markedge.editorial.revisions_per_record', 100);
        $ids = $this->latestQuery($record)->skip($keep)->take(500)->pluck('id');

        if ($ids->isNotEmpty()) {
            ContentRevision::query()->whereKey($ids)->delete();
        }
    }

    /**
     * @return Builder<ContentRevision>
     */
    protected function latestQuery(Model $record)
    {
        return ContentRevision::query()
            ->where('revisionable_type', $record->getMorphClass())
            ->where('revisionable_id', $record->getKey())
            ->orderByDesc('version');
    }
}
