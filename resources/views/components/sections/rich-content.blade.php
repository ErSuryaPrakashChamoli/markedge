{{-- Sanitised rich text with optional heading. Hidden when the HTML has no text. --}}
@props(['html', 'heading' => null, 'theme' => 'light', 'id' => null, 'container' => 'prose'])
@if (filled(strip_tags((string) $html)))
    <x-ui.section :theme="$theme" :id="$id" :container="$container">
        @if ($heading)
            <h2 class="text-h2 mb-6">{{ $heading }}</h2>
        @endif
        <x-ui.prose :html="$html" />
    </x-ui.section>
@endif
