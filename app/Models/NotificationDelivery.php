<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Delivery log for the channel adapters. Bodies are never stored; only outcome and subject.
 */
#[Fillable(['channel', 'status', 'subject', 'recipient', 'idempotency_key', 'error', 'source', 'created_at'])]
class NotificationDelivery extends Model
{
    public const null UPDATED_AT = null;

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }
}
