@props(['id', 'type' => 'text', 'invalid' => false])
<input id="{{ $id }}" type="{{ $type }}" {{ $attributes->merge(['class' => 'field-control']) }} @if ($invalid) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif>
