{{-- Site logo. `inverse` is the light logo for the dark footer; `logoUrl` is the admin-uploaded image, falling back to the built-in files. --}}
@props(['companyName' => config('app.name'), 'logoUrl' => null, 'inverse' => false])
<a href="{{ url('/') }}" {{ $attributes->merge(['class' => 'inline-flex items-center gap-2']) }} aria-label="{{ $companyName }} home">
    @if ($inverse)
        <img src="{{ $logoUrl ?: asset('images/logo.png') }}" alt="{{ $companyName }}" class="h-14 w-auto max-w-full" width="93" height="56">
    @elseif ($logoUrl)
        <img src="{{ $logoUrl }}" alt="{{ $companyName }}" class="h-8 w-auto lg:h-9" width="160" height="48">
    @else
        <img src="{{ asset('images/logo-dark.png') }}" alt="{{ $companyName }}" class="h-8 w-auto theme-dark:hidden lg:h-9" width="74" height="48">
        <img src="{{ asset('images/logo.png') }}" alt="" aria-hidden="true" class="hidden h-8 w-auto theme-dark:block lg:h-9" width="80" height="48">
    @endif
</a>
