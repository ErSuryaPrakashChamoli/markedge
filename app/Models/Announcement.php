<?php

namespace App\Models;

use App\Enums\AnnouncementDisplay;
use App\Models\Concerns\BumpsContentVersion;
use Database\Factories\AnnouncementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['message', 'link_label', 'link_url', 'display', 'style', 'is_active', 'is_dismissible', 'starts_at', 'ends_at'])]
class Announcement extends Model
{
    /** @use HasFactory<AnnouncementFactory> */
    use BumpsContentVersion, HasFactory;

    protected function casts(): array
    {
        return [
            'display' => AnnouncementDisplay::class,
            'is_active' => 'boolean',
            'is_dismissible' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    #[Scope]
    protected function current(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()));
    }
}
