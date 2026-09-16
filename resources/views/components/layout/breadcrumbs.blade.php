{{-- $items: array of ['label' => string, 'url' => ?string]; the last item is the current page. --}}
@props(['items' => []])
@if (count($items) > 0)
    <nav aria-label="Breadcrumb" {{ $attributes->merge(['class' => 'text-caption text-fg-muted']) }}>
        <ol class="flex flex-wrap items-center gap-x-2 gap-y-1">
            <li><a href="{{ url('/') }}" class="hover:text-fg">Home</a></li>
            @foreach ($items as $item)
                <li aria-hidden="true"><x-ui.icon name="heroicon-m-chevron-right" class="size-3.5" /></li>
                <li>
                    @if ($loop->last || empty($item['url']))
                        <span @if ($loop->last) aria-current="page" @endif class="{{ $loop->last ? 'text-fg-secondary' : '' }}">{{ $item['label'] }}</span>
                    @else
                        <a href="{{ $item['url'] }}" class="hover:text-fg">{{ $item['label'] }}</a>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif
