@props(['id', 'rows' => 5, 'invalid' => false])
<textarea id="{{ $id }}" rows="{{ $rows }}" {{ $attributes->merge(['class' => 'field-control']) }} @if ($invalid) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif>{{ $slot }}</textarea>
