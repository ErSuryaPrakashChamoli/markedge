@props(['features'])
@if ($features->isNotEmpty())
    <div class="mt-6 space-y-5">
        @foreach ($features as $feature)
            <div class="border-l-2 border-line pl-4">
                <h4 class="text-h4">{{ $feature->title }}</h4>
                @if ($feature->description)<p class="mt-1 text-body-sm text-fg-secondary">{{ $feature->description }}</p>@endif
                <x-products.capabilities :capabilities="$feature->capabilities" />
            </div>
        @endforeach
    </div>
@endif
