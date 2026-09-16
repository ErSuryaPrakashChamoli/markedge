<?php

namespace App\Observers;

use App\Jobs\SyncSearchEntry;
use App\Models\SeoMeta;
use App\Search\SearchTypes;

/**
 * Noindex, canonical and sitemap changes live on the SEO row, so it must re-sync its owner.
 */
class SeoMetaObserver
{
    public function saved(SeoMeta $seo): void
    {
        $this->dispatch($seo);
    }

    public function deleted(SeoMeta $seo): void
    {
        $this->dispatch($seo);
    }

    protected function dispatch(SeoMeta $seo): void
    {
        if (in_array($seo->seoable_type, SearchTypes::keys(), true) && $seo->seoable_id) {
            SyncSearchEntry::dispatch($seo->seoable_type, (int) $seo->seoable_id);
        }
    }
}
