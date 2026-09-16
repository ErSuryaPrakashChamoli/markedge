<?php

namespace App\Models\Concerns;

use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Audit trail for editorial and configuration models. Each model lists the
 * attributes worth auditing in $activityLogAttributes (architecture §37).
 */
trait RecordsActivity
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly($this->activityLogAttributes ?? ['*'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('content');
    }
}
