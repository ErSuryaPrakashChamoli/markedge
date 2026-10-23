<?php

namespace App\Models;

use App\Models\Concerns\BumpsContentVersion;
use App\Models\Concerns\HasSortOrder;
use Database\Factories\GrievanceLevelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One step of the grievance escalation ladder. Sort order is the escalation order: the first level is contacted first.
 */
#[Fillable(['name', 'description', 'response_time', 'is_visible', 'sort_order'])]
class GrievanceLevel extends Model
{
    /** @use HasFactory<GrievanceLevelFactory> */
    use BumpsContentVersion, HasFactory, HasSortOrder;

    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
        ];
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(GrievanceContact::class)->orderBy('sort_order')->orderBy('id');
    }

    #[Scope]
    protected function visible(Builder $query): Builder
    {
        return $query->where('is_visible', true);
    }
}
