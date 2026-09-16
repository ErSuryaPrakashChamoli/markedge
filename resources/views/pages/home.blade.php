{{-- CMS-driven home page: the Page with slug "home" composed entirely of typed blocks (architecture §5). --}}
<x-layouts.app :meta="$meta" :preview="$preview">
    <x-cms.blocks :blocks="$blocks" :host="$entity" :preview="$preview" />
    @if ($blocks === [] && $cta)
        <x-cta.band :cta="$cta" />
    @endif
</x-layouts.app>
