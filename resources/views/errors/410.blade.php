<x-layouts.app title="Content removed" robots="noindex, nofollow">
    <x-ui.section pattern="grid" container="narrow">
        <x-ui.eyebrow>Error 410</x-ui.eyebrow>
        <h1 class="mt-3 text-h1">This content has been retired</h1>
        <p class="mt-4 max-w-xl text-body-lg text-fg-secondary">The page you requested is no longer published. The links below lead to current content.</p>
        <div class="mt-8 flex flex-wrap gap-3">
            <x-ui.button href="{{ url('/') }}">Go to the homepage</x-ui.button>
            <x-ui.button href="{{ url('/insights') }}" variant="outline">Browse insights</x-ui.button>
        </div>
    </x-ui.section>
</x-layouts.app>
