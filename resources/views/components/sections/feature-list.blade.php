{{-- Repeater-driven list of title/text(/icon) items (benefits, features, challenges, use cases). Hidden when empty. --}}
@props(['items' => [], 'heading' => null, 'intro' => null, 'columns' => 3, 'theme' => 'light', 'id' => null])
@php $items = collect($items)->filter(fn ($item) => filled($item['title'] ?? null))->values(); @endphp
@if ($items->isNotEmpty())
    <x-ui.section :theme="$theme" :id="$id">
        @if ($heading)
            <x-ui.section-header :title="$heading" :intro="$intro" class="mb-10" />
        @endif
        <x-ui.grid :cols="$columns">
            @foreach ($items as $item)
                <x-ui.card interactive>
                    <span class="inline-flex size-12 items-center justify-center rounded-control bg-brand-soft text-brand">
                        @if (filled($item['icon'] ?? null) && preg_match('/^heroicon-[a-z]-[a-z0-9-]+$/', $item['icon']))
                            <x-ui.icon :name="$item['icon']" class="size-6" />
                        @else
                            <span class="text-h4 font-bold tabular-nums">{{ str_pad((string) ($loop->iteration), 2, '0', STR_PAD_LEFT) }}</span>
                        @endif
                    </span>
                    <h3 class="mt-5 text-h4">{{ $item['title'] }}</h3>
                    @if (filled($item['text'] ?? null))
                        <p class="mt-2 text-body-sm text-fg-secondary">{{ $item['text'] }}</p>
                    @endif
                </x-ui.card>
            @endforeach
        </x-ui.grid>
    </x-ui.section>
@endif
