{{-- Conversion-focused shell: minimal header, reduced footer, sticky CTA on mobile (architecture §22). --}}
@props(['meta' => null, 'hideNavigation' => true, 'hideFooterLinks' => true, 'preview' => false])
<x-layouts.app :meta="$meta" :minimal-header="$hideNavigation" :minimal-footer="$hideFooterLinks" :show-sticky-cta="true" :preview="$preview" body-class="is-landing">
    {{ $slot }}
</x-layouts.app>
