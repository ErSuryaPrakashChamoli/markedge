<x-layouts.minimal title="Too many requests">
    <x-ui.container size="narrow" class="py-24">
        <p class="text-eyebrow text-brand">Error 429</p>
        <h1 class="mt-3 text-h1">Too many requests</h1>
        <p class="mt-4 max-w-xl text-body-lg text-fg-secondary">You have made too many requests in a short time. Please wait a moment and try again.</p>
        <p class="mt-8"><a href="{{ url('/') }}" class="inline-flex h-11 items-center rounded-control bg-brand px-5 text-button text-white hover:bg-brand-hover">Go to the homepage</a></p>
    </x-ui.container>
</x-layouts.minimal>
