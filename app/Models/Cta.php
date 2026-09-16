<?php

namespace App\Models;

use App\Enums\CtaAction;
use App\Enums\CtaVariant;
use App\Models\Concerns\BumpsContentVersion;
use App\Models\Concerns\RecordsActivity;
use Database\Factories\CtaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Reusable call to action. Templates never hard-code CTA copy; they render a Cta row.
 */
#[Fillable([
    'name', 'key', 'headline', 'body', 'primary_label', 'primary_action', 'primary_value',
    'secondary_label', 'secondary_action', 'secondary_value', 'whatsapp_message', 'variant', 'is_active',
])]
class Cta extends Model
{
    /** @use HasFactory<CtaFactory> */
    use BumpsContentVersion, HasFactory, RecordsActivity;

    /** @var array<int, string> */
    protected array $activityLogAttributes = ['key', 'primary_label', 'primary_action', 'primary_value', 'whatsapp_message', 'is_active'];

    protected function casts(): array
    {
        return [
            'primary_action' => CtaAction::class,
            'secondary_action' => CtaAction::class,
            'variant' => CtaVariant::class,
            'is_active' => 'boolean',
        ];
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(CtaClick::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    /**
     * WhatsApp message with the {entity} placeholder resolved for the current context.
     */
    public function whatsappMessageFor(?string $entityName = null): ?string
    {
        if ($this->whatsapp_message === null) {
            return null;
        }

        return str_replace('{entity}', $entityName ?? '', $this->whatsapp_message);
    }
}
