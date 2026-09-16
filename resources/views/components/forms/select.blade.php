@props(['id', 'invalid' => false, 'placeholder' => null, 'multiple' => false])
<select id="{{ $id }}" {{ $attributes->merge(['class' => 'field-control']) }} @if ($multiple) multiple @endif @if ($invalid) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif>
    @if ($placeholder && ! $multiple)
        <option value="">{{ $placeholder }}</option>
    @endif
    {{ $slot }}
</select>
