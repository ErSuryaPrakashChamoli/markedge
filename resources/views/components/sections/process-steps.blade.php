@props(['steps' => [], 'heading' => null, 'intro' => null, 'orientation' => 'horizontal', 'theme' => 'light', 'id' => null])
@php $steps = collect($steps)->filter(fn ($step) => filled($step['title'] ?? null))->values(); @endphp
@if ($steps->isNotEmpty())
    <x-ui.section :theme="$theme" :id="$id">
        @if ($heading)
            <x-ui.section-header :title="$heading" :intro="$intro" class="mb-10" />
        @endif
        <ol @class(['grid grid-cols-1 gap-8', 'md:grid-cols-2 lg:grid-cols-4' => $orientation === 'horizontal' && $steps->count() > 4, 'md:grid-cols-2 lg:grid-cols-'.min($steps->count(), 4) => $orientation === 'horizontal' && $steps->count() <= 4, 'max-w-2xl' => $orientation === 'vertical'])>
            @foreach ($steps as $step)
                <li class="relative pl-12">
                    <span class="absolute top-0 left-0 flex size-8 items-center justify-center rounded-pill bg-brand text-meta font-bold text-white" aria-hidden="true">{{ $loop->iteration }}</span>
                    <h3 class="text-h4"><span class="sr-only">Step {{ $loop->iteration }}: </span>{{ $step['title'] }}</h3>
                    @if (filled($step['text'] ?? null))
                        <p class="mt-2 text-body-sm text-fg-secondary">{{ $step['text'] }}</p>
                    @endif
                </li>
            @endforeach
        </ol>
    </x-ui.section>
@endif
