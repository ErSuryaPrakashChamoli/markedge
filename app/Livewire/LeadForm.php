<?php

namespace App\Livewire;

use App\Enums\FormFieldType;
use App\Enums\FormSuccessMode;
use App\Models\Form;
use App\Models\FormField;
use App\Models\Industry;
use App\Models\Lead;
use App\Models\Product;
use App\Models\Service;
use App\Models\Solution;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Renders a configured Form and stores a Lead (architecture §19). Attribution, notifications,
 * spam scoring and CTA click tracking are layered on in the lead-engine phase.
 */
class LeadForm extends Component
{
    #[Locked]
    public int $formId;

    /** @var array<string, int|string> */
    #[Locked]
    public array $context = [];

    #[Locked]
    public bool $preview = false;

    public ?string $heading = null;

    public ?string $intro = null;

    public string $layout = 'card';

    /** @var array<string, mixed> */
    public array $data = [];

    public string $website = '';

    public bool $consent = false;

    public bool $submitted = false;

    /**
     * @param  array<string, int|string>  $context
     */
    public function mount(Form $form, array $context = [], ?string $heading = null, ?string $intro = null, string $layout = 'card', bool $preview = false): void
    {
        $this->formId = $form->id;
        $this->context = $context;
        $this->heading = $heading ?? $form->heading;
        $this->intro = $intro ?? $form->intro;
        $this->layout = $layout;
        $this->preview = $preview;

        foreach (array_keys($form->enabledCoreFields()) as $field) {
            $this->data[$field] = '';
        }

        foreach ($form->fields as $field) {
            $this->data[$field->key] = $field->type === FormFieldType::Multiselect ? [] : '';
        }
    }

    public function submit(): void
    {
        $form = $this->form();

        if ($this->preview || ! $form->is_active) {
            return;
        }

        if ($form->honeypot_enabled && $this->website !== '') {
            // Bots fill the hidden field; pretend success without storing anything.
            $this->submitted = true;

            return;
        }

        $validated = Validator::make(
            ['data' => $this->data, 'consent' => $this->consent],
            $this->rules($form),
            [],
            $this->attributes($form),
        )->validate();

        $this->store($form, $validated['data']);

        $this->submitted = true;

        if ($form->success_mode === FormSuccessMode::Redirect && $form->successPage?->isPublished()) {
            $this->redirect('/'.$form->successPage->slug);
        }
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(Form $form): array
    {
        $rules = [];

        foreach ($form->enabledCoreFields() as $field => $config) {
            $rules["data.{$field}"] = array_filter([
                ($config['required'] ?? false) ? 'required' : 'nullable',
                'string',
                match ($field) {
                    'email' => 'email:rfc',
                    'phone' => 'regex:/^[+\d][\d\s().-]{5,}$/',
                    'message', 'requirement' => 'max:5000',
                    default => 'max:255',
                },
            ]);
        }

        foreach ($form->fields as $field) {
            $rules["data.{$field->key}"] = $this->fieldRules($field);
        }

        if ($form->requires_consent) {
            $rules['consent'] = ['accepted'];
        }

        return $rules;
    }

    /**
     * @return array<int, mixed>
     */
    protected function fieldRules(FormField $field): array
    {
        $rules = [$field->is_required ? 'required' : 'nullable'];
        $validation = $field->validation ?? [];

        $rules[] = match ($field->type) {
            FormFieldType::Email => 'email:rfc',
            FormFieldType::Number => 'numeric',
            FormFieldType::Date => 'date',
            FormFieldType::Checkbox => 'boolean',
            FormFieldType::Multiselect => 'array',
            default => 'string',
        };

        if ($field->type->hasOptions()) {
            $options = array_keys($this->optionsFor($field));
            $rules[] = $field->type === FormFieldType::Multiselect ? null : Rule::in($options);
        }

        if (($validation['url'] ?? false) && $field->type !== FormFieldType::Number) {
            $rules[] = 'url';
        }

        if (isset($validation['min']) && is_numeric($validation['min'])) {
            $rules[] = 'min:'.(int) $validation['min'];
        }

        if (isset($validation['max']) && is_numeric($validation['max'])) {
            $rules[] = 'max:'.(int) $validation['max'];
        } elseif (! in_array($field->type, [FormFieldType::Number, FormFieldType::Multiselect, FormFieldType::Checkbox, FormFieldType::Date], true)) {
            $rules[] = 'max:2000';
        }

        return array_values(array_filter($rules));
    }

    /**
     * @return array<string, string>
     */
    protected function attributes(Form $form): array
    {
        $attributes = [];

        foreach ($form->enabledCoreFields() as $field => $config) {
            $attributes["data.{$field}"] = strtolower($config['label'] ?? $field);
        }

        foreach ($form->fields as $field) {
            $attributes["data.{$field->key}"] = strtolower($field->label);
        }

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function store(Form $form, array $data): void
    {
        $core = array_intersect_key($data, array_flip(Form::CORE_FIELDS));
        $custom = [];
        $mapped = [];

        foreach ($form->fields as $field) {
            $value = $data[$field->key] ?? null;

            if ($field->maps_to && in_array($field->maps_to, FormField::MAPPABLE_COLUMNS, true) && filled($value)) {
                $mapped[$field->maps_to] = $value;
            } else {
                $custom[$field->label] = $value;
            }
        }

        Lead::query()->create(array_filter($core, fn ($value) => $value !== '') + $mapped + [
            'form_id' => $form->id,
            'landing_page_id' => $this->context['landing_page_id'] ?? null,
            'service_id' => $mapped['service_id'] ?? $this->context['service_id'] ?? null,
            'product_id' => $mapped['product_id'] ?? $this->context['product_id'] ?? null,
            'industry_id' => $mapped['industry_id'] ?? $this->context['industry_id'] ?? null,
            'custom_fields' => $custom ?: null,
            'submitted_from_url' => request()->headers->get('referer'),
            'consent_given_at' => $form->requires_consent ? now() : null,
            'user_agent' => request()->userAgent(),
            'ip' => config('markedge.privacy.store_ip') ? request()->ip() : null,
            'locale' => app()->getLocale(),
        ]);
    }

    /**
     * Options for select-style fields, from manual values or a live entity list.
     *
     * @return array<string, string>
     */
    public function optionsFor(FormField $field): array
    {
        $options = $field->options ?? [];

        $query = match ($options['source'] ?? null) {
            'services' => Service::query()->published()->ordered(),
            'products' => Product::query()->publiclyVisible()->ordered(),
            'industries' => Industry::query()->published()->ordered(),
            'solutions' => Solution::query()->published()->ordered(),
            default => null,
        };

        if ($query !== null) {
            $useIds = in_array($field->maps_to, ['service_id', 'product_id', 'industry_id', 'solution_id'], true);

            return $query->pluck('name', $useIds ? 'id' : 'name')->map(fn ($name) => (string) $name)->all();
        }

        $values = array_values(array_filter((array) ($options['values'] ?? []), 'is_string'));

        return array_combine($values, $values);
    }

    protected function form(): Form
    {
        return Form::query()->with(['fields', 'successPage'])->findOrFail($this->formId);
    }

    public function render(): View
    {
        $form = $this->form();

        return view('livewire.lead-form', [
            'form' => $form,
            'coreFields' => $form->enabledCoreFields(),
            'extraFields' => $form->fields->map(fn (FormField $field) => ['field' => $field, 'options' => $field->type->hasOptions() ? $this->optionsFor($field) : []]),
        ]);
    }
}
