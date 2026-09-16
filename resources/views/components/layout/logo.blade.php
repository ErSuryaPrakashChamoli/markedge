@props(['companyName' => config('app.name'), 'logoUrl' => null, 'inverse' => false])
<a href="{{ url('/') }}" {{ $attributes->merge(['class' => 'inline-flex items-center gap-2']) }} aria-label="{{ $companyName }} home">
    @if ($logoUrl)
        <img src="{{ $logoUrl }}" alt="{{ $companyName }}" class="h-8 w-auto" width="160" height="32">
    @else
        <span class="font-display text-[1.35rem] font-bold tracking-tight uppercase {{ $inverse ? 'text-white' : 'text-fg' }}">Mark<span class="text-brand">edge</span></span>
    @endif
</a>
