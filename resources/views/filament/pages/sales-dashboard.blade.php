<x-filament-panels::page>
    <div class="flex flex-wrap items-center gap-2">
        @foreach ($ranges as $key => $label)
            <x-filament::button size="sm" :color="$range === $key ? 'primary' : 'gray'" wire:click="setRange('{{ $key }}')">{{ $label }}</x-filament::button>
        @endforeach
    </div>
    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $report->from->format('d M Y') }} to {{ $report->until->copy()->subDay()->format('d M Y') }} ({{ $timezone }}). Spam is excluded. Pipeline and follow-up figures are current; outcomes use the closing date.</p>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem;">
        <x-filament::section heading="New enquiries"><p class="text-3xl font-semibold">{{ $outcomes['new'] }}</p></x-filament::section>
        <x-filament::section heading="Won"><p class="text-3xl font-semibold">{{ $outcomes['won'] }}</p><p class="text-xs text-gray-500">Win rate: {{ $outcomes['win_rate'] === null ? '— (no closed won/lost leads)' : $outcomes['win_rate'].' %' }}</p></x-filament::section>
        <x-filament::section heading="Lost / unqualified"><p class="text-3xl font-semibold">{{ $outcomes['lost'] }} / {{ $outcomes['unqualified'] }}</p></x-filament::section>
        <x-filament::section heading="Overdue follow-ups"><p class="text-3xl font-semibold">{{ $followUps['overdue'] }} @if ($followUps['overdue'] > 0)<x-filament::badge color="danger">attention</x-filament::badge>@endif</p><p class="text-xs text-gray-500">Due today: {{ $followUps['due_today'] }} · Upcoming: {{ $followUps['upcoming'] }}</p></x-filament::section>
    </div>

    <x-filament::section heading="Open pipeline" description="Open leads in each stage right now, with the age of the longest-waiting lead.">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: .75rem;">
            @foreach ($pipeline as $key => $stage)
                <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-900/60">
                    <p class="text-xs text-gray-500">{{ $stage['label'] }}</p>
                    <p class="text-2xl font-semibold">{{ $stage['count'] }}</p>
                    <p class="text-xs text-gray-500">{{ $stage['oldest_days'] === null ? '—' : 'oldest '.$stage['oldest_days'].' d' }}</p>
                </div>
            @endforeach
        </div>
    </x-filament::section>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem;">
        <x-filament::section heading="First contact" description="Time from enquiry to the first recorded contact.">
            @if (! $firstContact['configured'])
                <p class="text-sm text-gray-500">SLA target NOT CONFIGURED (MARKEDGE_SALES_FIRST_CONTACT_HOURS).</p>
            @else
                <p class="text-sm">Target: {{ $firstContact['target_hours'] }} h · Open leads past target without contact: <span class="font-semibold">{{ $firstContact['breaching'] }}</span> @if ($firstContact['breaching'] > 0)<x-filament::badge color="danger">breaching</x-filament::badge>@endif</p>
                <p class="text-sm">Contacted within target in this period: {{ $firstContact['within_target'] }} of {{ $firstContact['contacted_in_range'] }}</p>
            @endif
            <p class="text-sm">Average time to first contact: {{ $firstContact['average_hours'] === null ? '— (no leads contacted in this period)' : $firstContact['average_hours'].' h' }} over {{ $firstContact['contacted_in_range'] }} lead(s)</p>
        </x-filament::section>
        <x-filament::section heading="Entered pipeline value" description="Values typed by sales on open leads. Nothing is estimated.">
            @if ($value['total'] === null)
                <p class="text-sm text-gray-500">No deal values entered on {{ $value['open_leads'] }} open lead(s).</p>
            @else
                <p class="text-2xl font-semibold">{{ $value['total'] }} {{ $value['currency'] ?? '' }}</p>
                <p class="text-xs text-gray-500">{{ $value['leads_with_value'] }} of {{ $value['open_leads'] }} open leads have a value{{ $value['currency'] ? '' : ' · currency NOT CONFIGURED' }}.</p>
            @endif
        </x-filament::section>
        <x-filament::section heading="Owners" description="Open leads, overdue follow-ups and leads won in the period.">
            @if ($owners->isEmpty())<p class="text-sm text-gray-500">No assigned leads.</p>@else
            <table class="w-full text-sm"><thead><tr class="text-left text-gray-500"><th class="pb-2">Owner</th><th class="pb-2 text-right">Open</th><th class="pb-2 text-right">Overdue</th><th class="pb-2 text-right">Won</th></tr></thead><tbody>
                @foreach ($owners as $row)<tr class="border-t border-gray-100 dark:border-gray-800"><td class="py-1.5">{{ $row['label'] }}</td><td class="py-1.5 text-right">{{ $row['open'] }}</td><td class="py-1.5 text-right">@if ($row['overdue'] > 0)<x-filament::badge color="danger">{{ $row['overdue'] }}</x-filament::badge>@else 0 @endif</td><td class="py-1.5 text-right font-medium">{{ $row['won'] }}</td></tr>@endforeach
            </tbody></table>@endif
        </x-filament::section>
        <x-filament::section heading="My follow-ups" description="Your next open follow-ups.">
            @if ($myFollowUps->isEmpty())<p class="text-sm text-gray-500">Nothing due.</p>@else
            <ul class="divide-y divide-gray-100 text-sm dark:divide-gray-800">
                @foreach ($myFollowUps as $followUp)
                    <li class="flex items-center justify-between gap-2 py-1.5">
                        <a href="{{ \App\Filament\Resources\Leads\LeadResource::getUrl('view', ['record' => $followUp->lead_id]) }}" class="font-medium hover:underline">{{ $followUp->lead?->name ?? 'Enquiry #'.$followUp->lead_id }}</a>
                        <x-filament::badge :color="$followUp->isOverdue() ? 'danger' : 'gray'">{{ $followUp->type->getLabel() }} · {{ $followUp->due_at->format('d M H:i') }}</x-filament::badge>
                    </li>
                @endforeach
            </ul>@endif
        </x-filament::section>
        <x-filament::section heading="Lost reasons" description="Leads closed as lost in the period.">
            @if ($lostReasons->isEmpty())<p class="text-sm text-gray-500">No lost leads in this period.</p>@else
            <table class="w-full text-sm"><tbody>@foreach ($lostReasons as $row)<tr class="border-t border-gray-100 dark:border-gray-800"><td class="py-1.5">{{ $row['label'] }}</td><td class="py-1.5 text-right font-medium">{{ $row['count'] }}</td></tr>@endforeach</tbody></table>@endif
        </x-filament::section>
        <x-filament::section heading="Open leads by priority">
            <table class="w-full text-sm"><tbody>@foreach ($priorities as $row)<tr class="border-t border-gray-100 dark:border-gray-800"><td class="py-1.5">{{ $row['label'] }}</td><td class="py-1.5 text-right font-medium">{{ $row['count'] }}</td></tr>@endforeach</tbody></table>
        </x-filament::section>
        <x-filament::section heading="Leads received in the period by current stage">
            @if ($cohort->isEmpty())<p class="text-sm text-gray-500">No leads received in this period.</p>@else
            <table class="w-full text-sm"><tbody>@foreach ($cohort as $row)<tr class="border-t border-gray-100 dark:border-gray-800"><td class="py-1.5">{{ $row['label'] }}</td><td class="py-1.5 text-right font-medium">{{ $row['count'] }}</td></tr>@endforeach</tbody></table>@endif
        </x-filament::section>
        <x-filament::section heading="Teams">
            @if (! $teamsConfigured)<p class="text-sm text-gray-500">Teams NOT CONFIGURED (MARKEDGE_SALES_TEAMS).</p>@else
            <table class="w-full text-sm"><thead><tr class="text-left text-gray-500"><th class="pb-2">Team</th><th class="pb-2 text-right">Open</th><th class="pb-2 text-right">Won</th></tr></thead><tbody>@foreach ($teams as $row)<tr class="border-t border-gray-100 dark:border-gray-800"><td class="py-1.5">{{ $row['label'] }}</td><td class="py-1.5 text-right">{{ $row['open'] }}</td><td class="py-1.5 text-right font-medium">{{ $row['won'] }}</td></tr>@endforeach</tbody></table>@endif
        </x-filament::section>
    </div>
</x-filament-panels::page>
