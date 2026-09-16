<x-layouts.app title="Page not found" robots="noindex, nofollow">
    <x-ui.section pattern="grid" container="narrow">
        <x-ui.eyebrow>Error 404</x-ui.eyebrow>
        <h1 class="mt-3 text-h1">Page not found</h1>
        <p class="mt-4 max-w-xl text-body-lg text-fg-secondary">The page you are looking for has moved or no longer exists. Use the navigation above or start from one of these areas.</p>
        <div class="mt-8 flex flex-wrap gap-3">
            <x-ui.button href="{{ url('/') }}">Go to the homepage</x-ui.button>
            <x-ui.button href="{{ url('/services') }}" variant="outline">Explore services</x-ui.button>
            <x-ui.button href="{{ url('/products') }}" variant="outline">View products</x-ui.button>
        </div>
    </x-ui.section>
</x-layouts.app>
