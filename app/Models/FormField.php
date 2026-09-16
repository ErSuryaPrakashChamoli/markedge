<?php

namespace App\Models;

use App\Enums\FormFieldType;
use App\Models\Concerns\BumpsContentVersion;
use App\Models\Concerns\HasSortOrder;
use Database\Factories\FormFieldFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'form_id', 'key', 'label', 'type', 'placeholder', 'help_text', 'options', 'is_required',
    'validation', 'width', 'sort_order', 'maps_to',
])]
class FormField extends Model
{
    /** @use HasFactory<FormFieldFactory> */
    use BumpsContentVersion, HasFactory, HasSortOrder;

    /** Lead columns an extra field may write to instead of custom_fields. */
    public const array MAPPABLE_COLUMNS = ['service_id', 'product_id', 'industry_id', 'solution_id', 'requirement'];

    protected function casts(): array
    {
        return [
            'type' => FormFieldType::class,
            'options' => 'array',
            'validation' => 'array',
            'is_required' => 'boolean',
        ];
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }
}
