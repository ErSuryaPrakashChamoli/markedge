{{-- Displays a value exactly as entered; animates only when the value is numeric. --}}
@props(['value', 'label', 'note' => null])
@php $numeric = is_numeric(str_replace([',', ' '], '', (string) $value)); @endphp
<div {{ $attributes->merge(['class' => 'rounded-card border border-line bg-surface p-6 shadow-card']) }}>
    <p class="text-h1 text-gradient-brand" @if ($numeric) x-data="counter({{ (int) str_replace([',', ' '], '', $value) }})" x-text="display" @endif>{{ $value }}</p>
    <p class="mt-2 text-body font-semibold text-fg">{{ $label }}</p>
    @if ($note)
        <p class="mt-0.5 text-caption text-fg-muted">{{ $note }}</p>
    @endif
</div>
