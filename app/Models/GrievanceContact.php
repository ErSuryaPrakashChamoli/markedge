<?php

namespace App\Models;

use App\Models\Concerns\BumpsContentVersion;
use Database\Factories\GrievanceContactFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['grievance_level_id', 'name', 'designation', 'email', 'phone', 'address', 'sort_order'])]
class GrievanceContact extends Model
{
    /** @use HasFactory<GrievanceContactFactory> */
    use BumpsContentVersion, HasFactory;

    public function level(): BelongsTo
    {
        return $this->belongsTo(GrievanceLevel::class, 'grievance_level_id');
    }

    /**
     * Dialable form of the phone number for tel: links.
     */
    public function phoneHref(): ?string
    {
        return filled($this->phone) ? 'tel:'.preg_replace('/[^\d+]/', '', $this->phone) : null;
    }
}
