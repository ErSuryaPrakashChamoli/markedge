<?php

namespace App\Console\Commands;

use App\Search\SearchIndexer;
use Illuminate\Console\Command;

class SearchReindex extends Command
{
    protected $signature = 'markedge:search-reindex {--chunk=500 : Records per chunk}';

    protected $description = 'Rebuild the public search index from published, indexable content (never changes CMS data)';

    public function handle(SearchIndexer $indexer): int
    {
        $chunk = max(1, (int) $this->option('chunk'));
        $this->info("Rebuilding the search index in chunks of {$chunk}…");

        $total = $indexer->rebuild($chunk, function (string $type, int $indexed, int $scanned): void {
            $this->line(sprintf('  %-18s %5d indexed of %5d scanned', $type, $indexed, $scanned));
        });

        $this->info("Done. {$total} documents indexed.");

        return self::SUCCESS;
    }
}
