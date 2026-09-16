<?php

namespace App\Console\Commands;

use App\Services\Cms\Publisher;
use Illuminate\Console\Command;

class PublishScheduledContent extends Command
{
    protected $signature = 'content:publish-scheduled';

    protected $description = 'Publish scheduled content whose publish date has passed';

    public function handle(Publisher $publisher): int
    {
        $count = $publisher->publishDue();

        $this->info($count === 0 ? 'Nothing due for publishing.' : "Published {$count} scheduled record(s).");

        return self::SUCCESS;
    }
}
