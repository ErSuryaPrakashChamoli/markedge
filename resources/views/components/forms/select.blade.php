@props(['id', 'invalid' => false, 'placeholder' => null])
<select id="{{ $id }}" {{ $attributes->merge(['class' => 'field-control']) }} @if ($invalid) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif>
    @if ($placeholder)
        <option value="">{{ $placeholder }}</option>
    @endif
    {{ $slot }}
</select>
