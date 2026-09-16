{{-- Thin wrapper around Blade Icons so the icon set can change in one place. --}}
@props(['name'])
{{ svg($name, $attributes->merge(['class' => 'shrink-0', 'aria-hidden' => 'true'])->getAttributes()) }}
