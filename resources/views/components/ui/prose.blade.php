{{-- Sanitised CMS HTML. Only ever pass content that went through the purifier on save. --}}
@props(['html' => null])
<div {{ $attributes->merge(['class' => 'prose-mk']) }}>
    @if ($html !== null)
        {!! $html !!}
    @else
        {{ $slot }}
    @endif
</div>
