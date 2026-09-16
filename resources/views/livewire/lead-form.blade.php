<div @class(['rounded-card border border-line bg-surface card-p' => $layout === 'card'])>
    @if ($submitted)
        <div role="status" aria-live="polite" class="stack-text">
            <x-ui.icon name="heroicon-o-check-circle" class="size-8 text-success" />
            <p class="text-h3">{{ $form->success_message ?: 'Thank you. We will be in touch shortly.' }}</p>
        </div>
    @else
        @if ($heading)
            <h2 class="text-h3">{{ $heading }}</h2>
        @endif
        @if ($intro)
            <p class="mt-2 text-body text-fg-secondary">{{ $intro }}</p>
        @endif

        <form wire:submit="submit" class="mt-6" novalidate>
            <x-forms.grid>
                @foreach ($coreFields as $key => $config)
                    @php
                        $id = "lf-{$form->key}-{$key}";
                        $label = $config['label'] ?? ucfirst($key);
                        $error = $errors->first("data.{$key}");
                        $half = in_array($key, ['name', 'email', 'phone', 'company', 'country', 'city'], true);
                    @endphp
                    <x-forms.field :id="$id" :label="$label" :required="$config['required'] ?? false" :error="$error" :width="$half ? 'half' : 'full'">
                        @if (in_array($key, ['message', 'requirement'], true))
                            <x-forms.textarea :id="$id" wire:model="data.{{ $key }}" :placeholder="$config['placeholder'] ?? null" :invalid="(bool) $error" :required="$config['required'] ?? false" />
                        @else
                            <x-forms.input :id="$id" :type="match ($key) { 'email' => 'email', 'phone' => 'tel', default => 'text' }" wire:model="data.{{ $key }}" :placeholder="$config['placeholder'] ?? null" :invalid="(bool) $error" :required="$config['required'] ?? false" :autocomplete="match ($key) { 'name' => 'name', 'email' => 'email', 'phone' => 'tel', 'company' => 'organization', 'country' => 'country-name', 'city' => 'address-level2', default => 'on' }" />
                        @endif
                    </x-forms.field>
                @endforeach

                @foreach ($extraFields as $entry)
                    @php
                        $field = $entry['field'];
                        $id = "lf-{$form->key}-{$field->key}";
                        $error = $errors->first("data.{$field->key}");
                        $type = $field->type->value;
                    @endphp
                    @if ($type === 'hidden')
                        <input type="hidden" wire:model="data.{{ $field->key }}">
                    @elseif ($type === 'checkbox')
                        <div class="{{ $field->width === 'half' ? 'md:col-span-1' : 'md:col-span-2' }}">
                            <x-forms.checkbox :id="$id" :label="$field->label" wire:model="data.{{ $field->key }}" />
                            @if ($error)<p class="mt-1.5 text-caption text-danger" role="alert">{{ $error }}</p>@endif
                        </div>
                    @else
                        <x-forms.field :id="$id" :label="$field->label" :required="$field->is_required" :help="$field->help_text" :error="$error" :width="$field->width">
                            @if ($type === 'textarea')
                                <x-forms.textarea :id="$id" wire:model="data.{{ $field->key }}" :placeholder="$field->placeholder" :invalid="(bool) $error" />
                            @elseif (in_array($type, ['select', 'multiselect'], true))
                                <x-forms.select :id="$id" wire:model="data.{{ $field->key }}" :placeholder="$field->placeholder ?? 'Select an option'" :invalid="(bool) $error" :multiple="$type === 'multiselect'">
                                    @foreach ($entry['options'] as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </x-forms.select>
                            @elseif ($type === 'radio')
                                <div class="flex flex-wrap gap-4" role="radiogroup" aria-labelledby="{{ $id }}">
                                    @foreach ($entry['options'] as $value => $label)
                                        <label class="inline-flex items-center gap-2 text-body-sm text-fg-secondary">
                                            <input type="radio" class="field-check" wire:model="data.{{ $field->key }}" value="{{ $value }}"> {{ $label }}
                                        </label>
                                    @endforeach
                                </div>
                            @else
                                <x-forms.input :id="$id" :type="match ($type) { 'email' => 'email', 'tel' => 'tel', 'number' => 'number', 'date' => 'date', default => 'text' }" wire:model="data.{{ $field->key }}" :placeholder="$field->placeholder" :invalid="(bool) $error" />
                            @endif
                        </x-forms.field>
                    @endif
                @endforeach

                @if ($form->honeypot_enabled)
                    <div class="hidden" aria-hidden="true">
                        <label for="lf-{{ $form->key }}-website">Website</label>
                        <input id="lf-{{ $form->key }}-website" type="text" wire:model="website" tabindex="-1" autocomplete="off">
                    </div>
                @endif

                @if ($form->requires_consent)
                    <div class="md:col-span-2">
                        <x-forms.checkbox id="lf-{{ $form->key }}-consent" :label="$form->consentStatement()" wire:model="consent" />
                        @error('consent')<p class="mt-1.5 text-caption text-danger" role="alert">{{ $message }}</p>@enderror
                    </div>
                @endif

                <div class="md:col-span-2 flex items-center gap-4">
                    <x-ui.button type="submit" size="lg" :disabled="$preview" wire:loading.attr="disabled">{{ $form->submit_label }}</x-ui.button>
                    <span wire:loading class="text-caption text-fg-muted">Sending…</span>
                    @if ($preview)
                        <span class="text-caption text-fg-muted">Submissions are disabled in preview.</span>
                    @endif
                </div>
            </x-forms.grid>
        </form>
    @endif
</div>
