@props(['companyName' => config('app.name'), 'logoUrl' => null, 'inverse' => false])
<a href="{{ url('/') }}" {{ $attributes->merge(['class' => 'inline-flex items-center gap-2']) }} aria-label="{{ $companyName }} home">
    @if ($logoUrl)
        <img src="{{ $logoUrl }}" alt="{{ $companyName }}" class="h-8 w-auto lg:h-9" width="160" height="48">
    @elseif ($inverse)
        <img src="{{ asset('images/logo.png') }}" alt="{{ $companyName }}" class="h-14 w-auto" width="76" height="56">
    @else
        <img src="{{ asset('images/logo-dark.png') }}" alt="{{ $companyName }}" class="h-8 w-auto theme-dark:hidden lg:h-9" width="65" height="48">
        <img src="{{ asset('images/logo.png') }}" alt="" aria-hidden="true" class="hidden h-8 w-auto theme-dark:block lg:h-9" width="65" height="48">
    @endif
</a>
