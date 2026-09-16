<x-filament-panels::page>
    <div class="flex flex-wrap items-center gap-3">
        <span class="text-sm text-gray-500 dark:text-gray-400">Period</span>
        @foreach ([7, 30, 90, 365] as $option)
            <x-filament::button size="sm" :color="$days === $option ? 'primary' : 'gray'" wire:click="$set('days', {{ $option }})">{{ $option }} days</x-filament::button>
        @endforeach
        <span class="ml-auto text-sm font-medium">{{ $total }} enquiries (excluding spam)</span>
    </div>

    @if ($total === 0)
        <x-filament::section>
            <p class="text-sm text-gray-500 dark:text-gray-400">No enquiries in this period yet. Attribution appears here as soon as forms receive submissions.</p>
        </x-filament::section>
    @else
        <div class="grid gap-6 lg:grid-cols-2">
            @foreach (['First touch' => $firstTouch, 'Last touch' => $lastTouch] as $heading => $rows)
                <x-filament::section :heading="$heading">
                    <table class="w-full text-sm">
                        <thead><tr class="text-left text-gray-500"><th class="pb-2">Source</th><th class="pb-2">Medium</th><th class="pb-2 text-right">Leads</th></tr></thead>
                        <tbody>
                            @foreach ($rows as $row)
                                <tr class="border-t border-gray-100 dark:border-gray-800"><td class="py-1.5">{{ $row['source'] }}</td><td class="py-1.5">{{ $row['medium'] }}</td><td class="py-1.5 text-right font-medium">{{ $row['count'] }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </x-filament::section>
            @endforeach
            @foreach (['Campaigns' => $campaigns, 'Forms' => $forms] as $heading => $rows)
                <x-filament::section :heading="$heading">
                    @if ($rows->isEmpty())
                        <p class="text-sm text-gray-500">No data.</p>
                    @else
                        <table class="w-full text-sm">
                            <tbody>
                                @foreach ($rows as $row)
                                    <tr class="border-t border-gray-100 dark:border-gray-800"><td class="py-1.5">{{ $row['name'] }}</td><td class="py-1.5 text-right font-medium">{{ $row['count'] }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </x-filament::section>
            @endforeach
        </div>
    @endif
</x-filament-panels::page>
