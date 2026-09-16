@props(['id', 'label'])
<label for="{{ $id }}" class="flex items-start gap-3 text-body-sm text-fg-secondary">
    <input id="{{ $id }}" type="checkbox" {{ $attributes->merge(['class' => 'field-check mt-0.5']) }}>
    <span>{{ $label }}</span>
</label>
