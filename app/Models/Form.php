<?php

namespace App\Models;

use App\Enums\FormSuccessMode;
use App\Enums\FormType;
use App\Models\Concerns\BumpsContentVersion;
use App\Models\Concerns\RecordsActivity;
use Database\Factories\FormFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Hybrid form definition: core lead fields are toggled in core_fields,
 * extra admin-defined fields live in form_fields (architecture §19).
 */
#[Fillable([
    'name', 'key', 'type', 'heading', 'intro', 'submit_label', 'success_mode', 'success_message',
    'success_page_id', 'notify_emails', 'auto_reply_enabled', 'auto_reply_subject', 'auto_reply_body',
    'core_fields', 'is_active', 'honeypot_enabled', 'requires_consent', 'consent_text',
])]
class Form extends Model
{
    /** @use HasFactory<FormFactory> */
    use BumpsContentVersion, HasFactory, RecordsActivity, SoftDeletes;

    /** @var array<int, string> */
    protected array $activityLogAttributes = ['key', 'type', 'notify_emails', 'is_active', 'requires_consent', 'core_fields'];

    /** @var array<int, string> */
    public const array CORE_FIELDS = ['name', 'email', 'phone', 'company', 'country', 'city', 'requirement', 'message'];

    protected function casts(): array
    {
        return [
            'type' => FormType::class,
            'success_mode' => FormSuccessMode::class,
            'notify_emails' => 'array',
            'core_fields' => 'array',
            'auto_reply_enabled' => 'boolean',
            'is_active' => 'boolean',
            'honeypot_enabled' => 'boolean',
            'requires_consent' => 'boolean',
        ];
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function fields(): HasMany
    {
        return $this->hasMany(FormField::class)->orderBy('sort_order')->orderBy('id');
    }

    public function successPage(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'success_page_id');
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public const string DEFAULT_CONSENT_TEXT = 'I agree to be contacted about my enquiry and accept the privacy policy.';

    public function consentStatement(): string
    {
        return filled($this->consent_text) ? $this->consent_text : self::DEFAULT_CONSENT_TEXT;
    }

    /**
     * Core fields enabled for this form, in display order.
     *
     * @return array<string, array{enabled: bool, required: bool, label?: string, placeholder?: string, sort_order?: int}>
     */
    public function enabledCoreFields(): array
    {
        $fields = array_filter(
            $this->core_fields ?? [],
            fn (array $config): bool => ($config['enabled'] ?? false) === true,
        );

        uasort($fields, fn (array $a, array $b): int => ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0));

        return $fields;
    }
}
