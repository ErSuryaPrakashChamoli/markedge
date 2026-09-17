@props(['capabilities'])
@if ($capabilities->isNotEmpty())
    <ul class="mt-3 space-y-1.5">
        @foreach ($capabilities as $capability)
            <li class="flex items-start gap-2 text-body-sm text-fg-secondary">
                <x-ui.icon name="heroicon-m-check" class="mt-0.5 size-4 shrink-0 text-brand" />
                <span><span class="font-medium text-fg">{{ $capability->name }}</span>@if ($capability->description) <span>— {{ $capability->description }}</span>@endif</span>
            </li>
        @endforeach
    </ul>
@endif
