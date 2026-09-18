<x-filament-panels::page>
    <div class="flex flex-wrap items-center gap-2">
        @foreach ($ranges as $key => $label)
            @continue($key === 'custom')
            <x-filament::button size="sm" :color="$range === $key ? 'primary' : 'gray'" wire:click="setRange('{{ $key }}')">{{ $label }}</x-filament::button>
        @endforeach
        <form wire:submit="setRange('custom')" class="flex items-center gap-2">
            <label class="sr-only" for="report-from">From</label>
            <input id="report-from" type="date" wire:model="from" class="fi-input rounded-lg border border-solid border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900">
            <label class="sr-only" for="report-until">Until</label>
            <input id="report-until" type="date" wire:model="until" class="fi-input rounded-lg border border-solid border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900">
            <x-filament::button size="sm" type="submit" :color="$range === 'custom' ? 'primary' : 'gray'">Apply</x-filament::button>
        </form>
    </div>
    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $report->from->format('d M Y') }} to {{ $report->until->copy()->subDay()->format('d M Y') }} ({{ $timezone }}). Spam is excluded. Counts only: the site has no reliable visit denominator, so no conversion rate is shown.</p>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach (['page_views' => 'Page views', 'sessions' => 'Sessions', 'visitors' => 'Visitors', 'landing_sessions' => 'Landing views'] as $key => $label)
            <x-filament::section :heading="$label"><p class="text-3xl font-semibold">{{ $traffic[$key] }}</p></x-filament::section>
        @endforeach
        @foreach (['leads' => 'Leads', 'form_submissions' => 'Form submissions', 'cta_clicks' => 'CTA clicks', 'searches' => 'On-site searches'] as $key => $label)
            <x-filament::section :heading="$label"><p class="text-3xl font-semibold">{{ $totals[$key] }}</p></x-filament::section>
        @endforeach
    </div>

    <x-filament::section heading="Funnel" description="Rates are shown only where the denominator was measured (sessions from page views; leads for qualification and conversion).">
        <table class="w-full text-sm">
            <thead><tr class="text-left text-gray-500"><th class="pb-2">Stage</th><th class="pb-2 text-right">Count</th><th class="pb-2 text-right">Rate</th></tr></thead>
            <tbody>
                @foreach (['sessions' => 'Sessions', 'engaged_sessions' => 'Engaged sessions (2+ pages)', 'cta_sessions' => 'Sessions with a CTA click', 'leads' => 'Leads', 'qualified' => 'Qualified leads', 'converted' => 'Converted leads'] as $key => $label)
                    <tr class="border-t border-gray-100 dark:border-gray-800"><td class="py-1.5">{{ $label }}</td><td class="py-1.5 text-right font-medium">{{ $funnel[$key]['count'] }}</td><td class="py-1.5 text-right text-gray-500">{{ $funnel[$key]['rate'] === null ? '—' : $funnel[$key]['rate'].' %' }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </x-filament::section>

    <x-filament::section heading="Trend">
        <div class="mb-3 flex flex-wrap gap-2">
            @foreach (['daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly', 'quarterly' => 'Quarterly'] as $key => $label)
                <x-filament::button size="sm" :color="$granularity === $key ? 'primary' : 'gray'" wire:click="$set('granularity', '{{ $key }}')">{{ $label }}</x-filament::button>
            @endforeach
            <span class="ml-auto text-xs text-gray-500">Buckets in {{ $timezone }}</span>
        </div>
        @if ($trend->isEmpty())
            <p class="text-sm text-gray-500">No data in this period.</p>
        @else
            <table class="w-full text-sm">
                <thead><tr class="text-left text-gray-500"><th class="pb-2">Period</th><th class="pb-2 text-right">Page views</th><th class="pb-2 text-right">Sessions</th><th class="pb-2 text-right">Leads</th></tr></thead>
                <tbody>
                    @foreach ($trend as $row)
                        <tr class="border-t border-gray-100 dark:border-gray-800"><td class="py-1.5">{{ $row['period'] }}</td><td class="py-1.5 text-right">{{ $row['page_views'] }}</td><td class="py-1.5 text-right">{{ $row['sessions'] }}</td><td class="py-1.5 text-right font-medium">{{ $row['leads'] }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </x-filament::section>

    <div class="grid gap-6 lg:grid-cols-2">
        <x-filament::section heading="Conversion paths" description="Event sequences before a lead, from the lead's own anonymous visitor history.">
            @if ($paths->isEmpty())<p class="text-sm text-gray-500">No leads with a recorded path in this period.</p>@else
            <table class="w-full text-sm"><tbody>@foreach ($paths as $row)<tr class="border-t border-gray-100 dark:border-gray-800"><td class="py-1.5">{{ $row['label'] }}</td><td class="py-1.5 text-right font-medium">{{ $row['count'] }}</td></tr>@endforeach</tbody></table>@endif
        </x-filament::section>
        <x-filament::section heading="Content performance" description="Views, CTA clicks from the page, leads converting on it, and leads whose first landing it was.">
            @if ($content->isEmpty())<p class="text-sm text-gray-500">No data in this period.</p>@else
            <table class="w-full text-sm"><thead><tr class="text-left text-gray-500"><th class="pb-2">Page</th><th class="pb-2 text-right">Views</th><th class="pb-2 text-right">CTA</th><th class="pb-2 text-right">Leads</th><th class="pb-2 text-right">First landing</th></tr></thead><tbody>@foreach ($content as $row)<tr class="border-t border-gray-100 dark:border-gray-800"><td class="py-1.5 break-all">{{ $row['label'] }}</td><td class="py-1.5 text-right">{{ $row['views'] }}</td><td class="py-1.5 text-right">{{ $row['cta_clicks'] }}</td><td class="py-1.5 text-right font-medium">{{ $row['leads'] }}</td><td class="py-1.5 text-right">{{ $row['influenced'] }}</td></tr>@endforeach</tbody></table>@endif
        </x-filament::section>
        @foreach (['Product interest' => $productInterest, 'Service interest' => $serviceInterest] as $heading => $rows)
            <x-filament::section :heading="$heading" description="Page views of the entity and leads associated with it.">
                @if ($rows->isEmpty())<p class="text-sm text-gray-500">No data in this period.</p>@else
                <table class="w-full text-sm"><thead><tr class="text-left text-gray-500"><th class="pb-2">Name</th><th class="pb-2 text-right">Views</th><th class="pb-2 text-right">Leads</th></tr></thead><tbody>@foreach ($rows as $row)<tr class="border-t border-gray-100 dark:border-gray-800"><td class="py-1.5">{{ $row['label'] }}</td><td class="py-1.5 text-right">{{ $row['views'] }}</td><td class="py-1.5 text-right font-medium">{{ $row['leads'] }}</td></tr>@endforeach</tbody></table>@endif
            </x-filament::section>
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
