<x-filament-panels::page>
    <div class="grid gap-4 sm:grid-cols-3">
        <x-filament::section heading="Indexed documents"><p class="text-3xl font-semibold">{{ $audit['total'] }}</p></x-filament::section>
        <x-filament::section heading="Index health"><p class="text-lg font-semibold {{ $healthy ? 'text-success-600' : 'text-warning-600' }}">{{ $healthy ? 'In sync with published content' : 'Missing or stale documents found' }}</p></x-filament::section>
        <x-filament::section heading="Last full rebuild"><p class="text-lg font-semibold">{{ $audit['last_rebuilt_at'] ? \Illuminate\Support\Carbon::parse($audit['last_rebuilt_at'])->diffForHumans() : 'Never (incremental sync only)' }}</p></x-filament::section>
    </div>

    <x-filament::section heading="Content types" description="Discoverable = published and indexable right now. Missing = discoverable but not indexed. Stale = indexed but no longer discoverable.">
        <table class="w-full text-sm">
            <thead><tr class="text-left text-gray-500"><th class="pb-2">Type</th><th class="pb-2 text-right">Indexed</th><th class="pb-2 text-right">Discoverable</th><th class="pb-2 text-right">Missing</th><th class="pb-2 text-right">Stale</th></tr></thead>
            <tbody>
                @foreach ($audit['by_type'] as $type => $row)
                    <tr class="border-t border-gray-100 dark:border-gray-800">
                        <td class="py-1.5">{{ $labels[$type] ?? $type }}</td>
                        <td class="py-1.5 text-right">{{ $row['indexed'] }}</td>
                        <td class="py-1.5 text-right">{{ $row['discoverable'] }}</td>
                        <td class="py-1.5 text-right {{ $row['missing'] ? 'text-warning-600 font-semibold' : '' }}">{{ $row['missing'] }}</td>
                        <td class="py-1.5 text-right {{ $row['stale'] ? 'text-warning-600 font-semibold' : '' }}">{{ $row['stale'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">Incremental sync runs automatically when content is saved. Use "Rebuild index" after imports or if the table above shows missing or stale documents. Command line: <code>php artisan markedge:search-reindex</code>.</p>
    </x-filament::section>
</x-filament-panels::page>
