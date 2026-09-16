<x-filament-panels::page>
    <p class="text-sm text-gray-500 dark:text-gray-400">Factual diagnostics over {{ $counts['scanned'] }} records. Counts are database aggregates; block and link scans are cached per content version. There is no health score.</p>

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($labels as $key => $label)
            <button type="button" wire:click="$set('list', '{{ $key }}')" class="rounded-xl border p-4 text-left {{ $selected === $key ? 'border-primary-500 bg-primary-50 dark:bg-primary-950' : 'border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900' }}">
                <p class="text-2xl font-semibold">{{ $counts[$key] }}</p>
                <p class="text-xs text-gray-500">{{ $label }}</p>
            </button>
        @endforeach
        <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900"><p class="text-2xl font-semibold">{{ $counts['noindex'] }}</p><p class="text-xs text-gray-500">Noindex</p></div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900"><p class="text-2xl font-semibold">{{ $counts['canonicalized'] }}</p><p class="text-xs text-gray-500">Canonicalized elsewhere</p></div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900"><p class="text-2xl font-semibold">{{ $counts['unowned'] }}</p><p class="text-xs text-gray-500">Without an owner</p></div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900"><p class="text-2xl font-semibold">{{ $counts['expiring_soon'] }}</p><p class="text-xs text-gray-500">Expiring soon</p></div>
    </div>

    <x-filament::section :heading="$labels[$selected]">
        @if ($items->isEmpty())
            <p class="text-sm text-gray-500">Nothing here.</p>
        @else
            <table class="w-full text-sm">
                <tbody>
                    @foreach ($items as $item)
                        <tr class="border-t border-gray-100 dark:border-gray-800">
                            <td class="py-2 font-medium">{{ $item->title }}</td>
                            <td class="py-2 text-gray-500">{{ $typeLabels[$item->type] ?? $item->type }}</td>
                            <td class="py-2 text-gray-500">{{ $item->detail ?? ($item->status ?? '') }}</td>
                            <td class="py-2 text-right"><a href="{{ $this->deskUrl($item->type, $item->id) }}" class="text-primary-600 hover:underline">Open</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </x-filament::section>
</x-filament-panels::page>
