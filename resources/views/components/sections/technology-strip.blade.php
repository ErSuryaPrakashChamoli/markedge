@props(['technologies', 'heading' => 'Technology', 'intro' => null, 'theme' => 'neutral', 'id' => null])
@if ($technologies->isNotEmpty())
    <x-ui.section :theme="$theme" spacing="sm" :id="$id">
        <x-ui.section-header :title="$heading" :intro="$intro" size="h3" class="mb-6" />
        <ul class="flex flex-wrap gap-3">
            @foreach ($technologies as $technology)
                <li><x-cards.technology :technology="$technology" /></li>
            @endforeach
        </ul>
    </x-ui.section>
@endif
