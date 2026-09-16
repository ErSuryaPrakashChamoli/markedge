@props(['id', 'type' => 'text', 'invalid' => false, 'maxlength' => null])
<input id="{{ $id }}" type="{{ $type }}" @if ($maxlength) maxlength="{{ $maxlength }}" @endif {{ $attributes->merge(['class' => 'field-control']) }} @if ($invalid) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif>
