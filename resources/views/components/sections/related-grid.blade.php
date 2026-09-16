{{-- Generic "related X" section: pass a collection and the card type. Hidden when empty. --}}
@props(['items', 'heading', 'intro' => null, 'card', 'cols' => 3, 'theme' => 'light', 'id' => null, 'moreLabel' => null, 'moreHref' => null])
@if ($items->isNotEmpty())
    <x-ui.section :theme="$theme" :id="$id">
        <div class="mb-10 flex flex-wrap items-end justify-between gap-4">
            <x-ui.section-header :title="$heading" :intro="$intro" />
            @if ($moreLabel && $moreHref)
                <x-ui.arrow-link :href="$moreHref">{{ $moreLabel }}</x-ui.arrow-link>
            @endif
        </div>
        <x-ui.grid :cols="$cols">
            @foreach ($items as $item)
                @switch($card)
                    @case('service') <x-cards.service :service="$item" show-category /> @break
                    @case('product') <x-cards.product :product="$item" /> @break
                    @case('solution') <x-cards.solution :solution="$item" /> @break
                    @case('industry') <x-cards.industry :industry="$item" /> @break
                    @case('case-study') <x-cards.case-study :case-study="$item" /> @break
                    @case('article') <x-cards.article :article="$item" /> @break
                @endswitch
            @endforeach
        </x-ui.grid>
    </x-ui.section>
@endif
