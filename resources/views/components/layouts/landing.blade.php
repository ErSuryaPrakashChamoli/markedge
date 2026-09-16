@props(['title' => null, 'description' => null, 'canonical' => null, 'robots' => 'noindex, nofollow', 'hideFooterLinks' => true])
<x-layouts.app :title="$title" :description="$description" :canonical="$canonical" :robots="$robots" :minimal-header="true" :minimal-footer="$hideFooterLinks" :show-sticky-cta="true" body-class="is-landing">
    {{ $slot }}
</x-layouts.app>
