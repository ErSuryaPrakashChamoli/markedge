<?php

namespace App\Console\Commands;

use App\Services\Cms\Publisher;
use Illuminate\Console\Command;

class PublishScheduledContent extends Command
{
    protected $signature = 'content:publish-scheduled';

    protected $description = 'Publish scheduled content whose publish date has passed and unpublish content past its unpublish date';

    public function handle(Publisher $publisher): int
    {
        $count = $publisher->publishDue();
        $expired = $publisher->unpublishDue();

        $this->info($count === 0 ? 'Nothing due for publishing.' : "Published {$count} scheduled record(s).");
        $this->info($expired === 0 ? 'Nothing due for unpublishing.' : "Unpublished {$expired} expired record(s).");

        return self::SUCCESS;
    }
}
