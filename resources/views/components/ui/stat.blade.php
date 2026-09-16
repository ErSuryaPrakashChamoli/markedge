{{-- Displays a value exactly as entered; animates only when the value is numeric. --}}
@props(['value', 'label', 'note' => null])
@php $numeric = is_numeric(str_replace([',', ' '], '', (string) $value)); @endphp
<div {{ $attributes->merge(['class' => 'border-l-2 border-brand pl-4']) }}>
    <p class="text-h1 text-fg" @if ($numeric) x-data="counter({{ (int) str_replace([',', ' '], '', $value) }})" x-text="display" @endif>{{ $value }}</p>
    <p class="mt-1 text-body-sm font-medium text-fg-secondary">{{ $label }}</p>
    @if ($note)
        <p class="mt-0.5 text-caption text-fg-muted">{{ $note }}</p>
    @endif
</div>
