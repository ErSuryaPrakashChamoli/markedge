<x-filament-panels::page>
    <div class="flex flex-wrap items-center gap-2">
        @foreach ($ranges as $key => $label)
            <x-filament::button size="sm" :color="$range === $key ? 'primary' : 'gray'" wire:click="setRange('{{ $key }}')">{{ $label }}</x-filament::button>
        @endforeach
        <span class="ml-auto text-xs text-gray-500">{{ $report->marketing->from->format('d M Y') }} to {{ $report->marketing->until->copy()->subDay()->format('d M Y') }} ({{ $timezone }})</span>
    </div>

    @if ($funnel !== null)
        <x-filament::section heading="Business funnel" description="Sessions from first-party page views; leads, qualified and won from the sales pipeline. Rates only where the denominator was measured.">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: .75rem;">
                @foreach (['sessions' => 'Sessions', 'leads' => 'Leads', 'qualified' => 'Qualified', 'won' => 'Won'] as $key => $label)
                    <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-900/60">
                        <p class="text-xs text-gray-500">{{ $label }}</p>
                        <p class="text-2xl font-semibold">{{ $funnel[$key]['count'] }}</p>
                        <p class="text-xs text-gray-500">{{ $funnel[$key]['rate'] === null ? '—' : $funnel[$key]['rate'].' %' }}</p>
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        <x-filament::section heading="Trend">
            <div class="mb-3 flex flex-wrap gap-2">
                @foreach (['daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly', 'quarterly' => 'Quarterly'] as $key => $label)
                    <x-filament::button size="sm" :color="$granularity === $key ? 'primary' : 'gray'" wire:click="$set('granularity', '{{ $key }}')">{{ $label }}</x-filament::button>
                @endforeach
            </div>
            @if ($trend->isEmpty())<p class="text-sm text-gray-500">No data in this period.</p>@else
            <table class="w-full text-sm"><thead><tr class="text-left text-gray-500"><th class="pb-2">Period</th><th class="pb-2 text-right">Page views</th><th class="pb-2 text-right">Sessions</th><th class="pb-2 text-right">Leads</th><th class="pb-2 text-right">Won</th></tr></thead><tbody>
                @foreach ($trend as $row)<tr class="border-t border-gray-100 dark:border-gray-800"><td class="py-1.5">{{ $row['period'] }}</td><td class="py-1.5 text-right">{{ $row['page_views'] }}</td><td class="py-1.5 text-right">{{ $row['sessions'] }}</td><td class="py-1.5 text-right">{{ $row['leads'] }}</td><td class="py-1.5 text-right font-medium">{{ $row['won'] }}</td></tr>@endforeach
            </tbody></table>@endif
        </x-filament::section>
    @endif

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem;">
        @if ($sections['marketing'])
            <x-filament::section heading="Source performance" description="Leads by last-touch source / medium.">
                @if ($sources->isEmpty())<p class="text-sm text-gray-500">No leads in this period.</p>@else
                <table class="w-full text-sm"><tbody>@foreach ($sources as $row)<tr class="border-t border-gray-100 dark:border-gray-800"><td class="py-1.5">{{ $row['label'] }}</td><td class="py-1.5 text-right font-medium">{{ $row['count'] }}</td></tr>@endforeach</tbody></table>@endif
            </x-filament::section>
            <x-filament::section heading="Content performance" description="Views, CTA clicks and leads per page.">
                @if ($content->isEmpty())<p class="text-sm text-gray-500">No data in this period.</p>@else
                <table class="w-full text-sm"><thead><tr class="text-left text-gray-500"><th class="pb-2">Page</th><th class="pb-2 text-right">Views</th><th class="pb-2 text-right">CTA</th><th class="pb-2 text-right">Leads</th></tr></thead><tbody>@foreach ($content->take(15) as $row)<tr class="border-t border-gray-100 dark:border-gray-800"><td class="py-1.5 break-all">{{ $row['label'] }}</td><td class="py-1.5 text-right">{{ $row['views'] }}</td><td class="py-1.5 text-right">{{ $row['cta_clicks'] }}</td><td class="py-1.5 text-right font-medium">{{ $row['leads'] }}</td></tr>@endforeach</tbody></table>@endif
            </x-filament::section>
        @endif
        @if ($sections['sales'])
            <x-filament::section heading="Sales performance" description="Current open pipeline and outcomes closed in the period.">
                <p class="text-sm">Won {{ $outcomes['won'] }} · Lost {{ $outcomes['lost'] }} · Unqualified {{ $outcomes['unqualified'] }} · Win rate {{ $outcomes['win_rate'] === null ? '—' : $outcomes['win_rate'].' %' }}</p>
                <table class="mt-3 w-full text-sm"><tbody>@foreach ($pipeline as $stage)<tr class="border-t border-gray-100 dark:border-gray-800"><td class="py-1.5">{{ $stage['label'] }}</td><td class="py-1.5 text-right font-medium">{{ $stage['count'] }}</td></tr>@endforeach</tbody></table>
            </x-filament::section>
            <x-filament::section heading="Owners" description="Open leads, overdue follow-ups, won in the period.">
                @if ($owners->isEmpty())<p class="text-sm text-gray-500">No assigned leads.</p>@else
                <table class="w-full text-sm"><tbody>@foreach ($owners as $row)<tr class="border-t border-gray-100 dark:border-gray-800"><td class="py-1.5">{{ $row['label'] }}</td><td class="py-1.5 text-right">{{ $row['open'] }} open</td><td class="py-1.5 text-right">{{ $row['overdue'] }} overdue</td><td class="py-1.5 text-right font-medium">{{ $row['won'] }} won</td></tr>@endforeach</tbody></table>@endif
            </x-filament::section>
        @endif
        @if ($sections['product'])
            <x-filament::section heading="Product performance" description="Page views, leads and leads won per product.">
                @if ($products->isEmpty())<p class="text-sm text-gray-500">No product data in this period.</p>@else
                <table class="w-full text-sm"><thead><tr class="text-left text-gray-500"><th class="pb-2">Product</th><th class="pb-2 text-right">Views</th><th class="pb-2 text-right">Leads</th><th class="pb-2 text-right">Won</th></tr></thead><tbody>@foreach ($products as $row)<tr class="border-t border-gray-100 dark:border-gray-800"><td class="py-1.5">{{ $row['label'] }}</td><td class="py-1.5 text-right">{{ $row['views'] }}</td><td class="py-1.5 text-right">{{ $row['leads'] }}</td><td class="py-1.5 text-right font-medium">{{ $row['won'] }}</td></tr>@endforeach</tbody></table>@endif
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
