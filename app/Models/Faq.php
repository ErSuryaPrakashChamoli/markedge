<?php

namespace App\Models;

use App\Models\Concerns\BumpsContentVersion;
use App\Models\Concerns\HasSortOrder;
use Database\Factories\FaqFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['faqable_type', 'faqable_id', 'question', 'answer', 'is_visible', 'sort_order'])]
class Faq extends Model
{
    /** @use HasFactory<FaqFactory> */
    use BumpsContentVersion, HasFactory, HasSortOrder;

    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
        ];
    }

    #[Scope]
    protected function visible(Builder $query): Builder
    {
        return $query->where('is_visible', true);
    }

    public function faqable(): MorphTo
    {
        return $this->morphTo();
    }
}
