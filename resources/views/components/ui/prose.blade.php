{{-- CMS rich text. Always printed through the sanitiser: inline styles, classes and scripts never reach the page. --}}
@props(['html' => null])
<div {{ $attributes->merge(['class' => 'prose-mk']) }}>
    @if ($html !== null)
        {!! app(\App\Cms\RichTextSanitizer::class)->sanitize($html) !!}
    @else
        {{ $slot }}
    @endif
</div>
