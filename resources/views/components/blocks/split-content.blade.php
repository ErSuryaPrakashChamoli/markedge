@props(['data', 'host' => null, 'preview' => false])
<x-ui.section :theme="$data['theme'] ?? 'light'" :id="$data['anchor'] ?? null">
    <x-ui.split :ratio="$data['imageUrl'] ? 'equal' : 'wide-left'" :reverse-on-mobile="false">
        <x-slot:left>
            @if (($data['media_position'] ?? 'right') === 'left' && $data['imageUrl'])
                <img src="{{ $data['imageUrl'] }}" alt="{{ $data['image_alt'] ?? '' }}" class="h-auto w-full rounded-card object-cover" loading="lazy" decoding="async">
            @else
                <h2 class="text-h2">{{ $data['heading'] }}</h2>
                <x-ui.prose :html="$data['body']" class="mt-5" />
                @if (filled($data['bullets'] ?? null))
                    <ul class="mt-6 space-y-2">
                        @foreach ($data['bullets'] as $bullet)
                            @if (filled($bullet))<li class="flex items-start gap-3 text-body text-fg"><x-ui.icon name="heroicon-m-check" class="mt-1 size-4 text-brand" />{{ $bullet }}</li>@endif
                        @endforeach
                    </ul>
                @endif
                @if ($data['cta'])
                    <div class="mt-8"><x-ui.button :href="$data['cta']['href']" icon="heroicon-m-arrow-right">{{ $data['cta']['label'] }}</x-ui.button></div>
                @endif
            @endif
        </x-slot:left>
        <x-slot:right>
            @if (($data['media_position'] ?? 'right') === 'left' && $data['imageUrl'])
                <h2 class="text-h2">{{ $data['heading'] }}</h2>
                <x-ui.prose :html="$data['body']" class="mt-5" />
                @if ($data['cta'])
                    <div class="mt-8"><x-ui.button :href="$data['cta']['href']" icon="heroicon-m-arrow-right">{{ $data['cta']['label'] }}</x-ui.button></div>
                @endif
            @elseif ($data['imageUrl'])
                <img src="{{ $data['imageUrl'] }}" alt="{{ $data['image_alt'] ?? '' }}" class="h-auto w-full rounded-card object-cover" loading="lazy" decoding="async">
            @else
                <div class="aspect-[4/3] w-full rounded-card border border-line bg-canvas-muted bg-grid-pattern" aria-hidden="true"></div>
            @endif
        </x-slot:right>
    </x-ui.split>
</x-ui.section>
