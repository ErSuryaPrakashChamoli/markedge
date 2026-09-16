<x-filament-panels::page>
    <div class="flex flex-wrap items-center gap-2">
        @foreach ($ranges as $key => $label)
            @continue($key === 'custom')
            <x-filament::button size="sm" :color="$range === $key ? 'primary' : 'gray'" wire:click="setRange('{{ $key }}')">{{ $label }}</x-filament::button>
        @endforeach
        <form wire:submit="setRange('custom')" class="flex items-center gap-2">
            <label class="sr-only" for="report-from">From</label>
            <input id="report-from" type="date" wire:model="from" class="fi-input rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900">
            <label class="sr-only" for="report-until">Until</label>
            <input id="report-until" type="date" wire:model="until" class="fi-input rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900">
            <x-filament::button size="sm" type="submit" :color="$range === 'custom' ? 'primary' : 'gray'">Apply</x-filament::button>
        </form>
    </div>
    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $report->from->format('d M Y') }} to {{ $report->until->copy()->subDay()->format('d M Y') }} ({{ $timezone }}). Spam is excluded. Counts only: the site has no reliable visit denominator, so no conversion rate is shown.</p>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
        @foreach (['leads' => 'Leads', 'form_submissions' => 'Form submissions', 'cta_clicks' => 'CTA clicks', 'searches' => 'On-site searches', 'leads_after_search' => 'Leads preceded by a search'] as $key => $label)
            <x-filament::section :heading="$label"><p class="text-3xl font-semibold">{{ $totals[$key] }}</p></x-filament::section>
        @endforeach
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        @foreach ($sections as $heading => $rows)
            <x-filament::section :heading="$heading">
                @if ($rows->isEmpty())
                    <p class="text-sm text-gray-500 dark:text-gray-400">No data in this period.</p>
                @else
                    <table class="w-full text-sm">
                        <tbody>
                            @foreach ($rows as $row)
                                <tr class="border-t border-gray-100 dark:border-gray-800"><td class="py-1.5 break-all">{{ $row['label'] }}</td><td class="py-1.5 text-right font-medium">{{ $row['count'] }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </x-filament::section>
        @endforeach

        @if ($searches !== null)
            <x-filament::section heading="Top search terms" description="Aggregated counts only; individual search histories are never shown.">
                @if ($searches->isEmpty())
                    <p class="text-sm text-gray-500 dark:text-gray-400">No searches in this period.</p>
                @else
                    <table class="w-full text-sm">
                        <tbody>
                            @foreach ($searches as $row)
                                <tr class="border-t border-gray-100 dark:border-gray-800"><td class="py-1.5">{{ $row['label'] }}</td><td class="py-1.5 text-right font-medium">{{ $row['count'] }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
