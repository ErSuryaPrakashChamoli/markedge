<x-filament-panels::page>
    <p class="text-sm text-gray-500 dark:text-gray-400">Everything on this page is read from the database or configuration at load time. Nothing here is a monitoring service; external monitoring remains NOT CONFIGURED until Phase 11B.</p>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
        <x-filament::section heading="Dependencies">
            <table class="w-full text-sm"><tbody>
                @foreach ($dependencies as $name => $check)
                    <tr class="border-t border-gray-100 dark:border-gray-800"><td class="py-1.5 capitalize">{{ $name }}</td><td class="py-1.5 text-right"><x-filament::badge :color="$check['ok'] ? 'success' : 'danger'">{{ $check['detail'] }}</x-filament::badge></td></tr>
                @endforeach
            </tbody></table>
        </x-filament::section>
        <x-filament::section heading="Queue">
            <table class="w-full text-sm"><tbody>
                <tr><td class="py-1.5">Driver</td><td class="py-1.5 text-right">{{ $queue['driver'] }}</td></tr>
                <tr class="border-t border-gray-100 dark:border-gray-800"><td class="py-1.5">Pending jobs</td><td class="py-1.5 text-right">{{ $queue['pending'] ?? 'n/a for this driver' }}</td></tr>
                <tr class="border-t border-gray-100 dark:border-gray-800"><td class="py-1.5">Oldest pending</td><td class="py-1.5 text-right">{{ $queue['oldest_pending_minutes'] === null ? '—' : $queue['oldest_pending_minutes'].' min' }}</td></tr>
                <tr class="border-t border-gray-100 dark:border-gray-800"><td class="py-1.5">Failed jobs (total / 24h)</td><td class="py-1.5 text-right">{{ $queue['failed'] ?? '—' }} / {{ $queue['failed_24h'] ?? '—' }} @if (($queue['failed_24h'] ?? 0) > 0)<x-filament::badge color="danger">check</x-filament::badge>@endif</td></tr>
            </tbody></table>
        </x-filament::section>
        <x-filament::section heading="Automation (24h)">
            <table class="w-full text-sm"><tbody>
                <tr><td class="py-1.5">Rules (active / total)</td><td class="py-1.5 text-right">{{ $automation['active'] }} / {{ $automation['rules'] }}</td></tr>
                <tr class="border-t border-gray-100 dark:border-gray-800"><td class="py-1.5">Runs</td><td class="py-1.5 text-right">{{ $automation['runs_24h'] }}</td></tr>
                <tr class="border-t border-gray-100 dark:border-gray-800"><td class="py-1.5">Succeeded / skipped / failed</td><td class="py-1.5 text-right">{{ $automation['succeeded_24h'] }} / {{ $automation['skipped_24h'] }} / {{ $automation['failed_24h'] }} @if ($automation['failed_24h'] > 0)<x-filament::badge color="danger">failures</x-filament::badge>@endif</td></tr>
                <tr class="border-t border-gray-100 dark:border-gray-800"><td class="py-1.5">Last run</td><td class="py-1.5 text-right">{{ $automation['last_run_at'] ?? 'never' }}</td></tr>
            </tbody></table>
        </x-filament::section>
        <x-filament::section heading="Notification channels">
            <table class="w-full text-sm"><tbody>
                @foreach ($channels as $name => $channel)
                    <tr class="border-t border-gray-100 dark:border-gray-800"><td class="py-1.5 capitalize">{{ $name }}<span class="block text-xs text-gray-500">{{ $channel['status'] }}</span></td><td class="py-1.5 text-right text-xs">sent {{ $channel['sent_24h'] }} · skipped {{ $channel['skipped_24h'] }} · failed {{ $channel['failed_24h'] }}</td></tr>
                @endforeach
            </tbody></table>
        </x-filament::section>
        <x-filament::section heading="API v1">
            <table class="w-full text-sm"><tbody>
                <tr><td class="py-1.5">Keys (active / total)</td><td class="py-1.5 text-right">{{ $api['active'] }} / {{ $api['keys'] }}</td></tr>
                <tr class="border-t border-gray-100 dark:border-gray-800"><td class="py-1.5">Last used</td><td class="py-1.5 text-right">{{ $api['last_used_at'] ?? 'never' }}</td></tr>
                <tr class="border-t border-gray-100 dark:border-gray-800"><td class="py-1.5">Audited requests / writes (24h)</td><td class="py-1.5 text-right">{{ $api['requests_24h'] }} / {{ $api['writes_24h'] }}</td></tr>
                <tr class="border-t border-gray-100 dark:border-gray-800"><td class="py-1.5">Rate limit per key</td><td class="py-1.5 text-right">{{ $api['rate_limit_per_minute'] }} / min</td></tr>
            </tbody></table>
        </x-filament::section>
        <x-filament::section heading="Data">
            <table class="w-full text-sm"><tbody>
                <tr><td class="py-1.5">Search index entries</td><td class="py-1.5 text-right">{{ $data['search_entries'] }}</td></tr>
                <tr class="border-t border-gray-100 dark:border-gray-800"><td class="py-1.5">Conversion events</td><td class="py-1.5 text-right">{{ $data['conversion_events'] }}</td></tr>
                <tr class="border-t border-gray-100 dark:border-gray-800"><td class="py-1.5">Oldest page view</td><td class="py-1.5 text-right">{{ $data['oldest_page_view'] ?? '—' }} (retention {{ $data['pageview_retention_days'] }} d)</td></tr>
                <tr class="border-t border-gray-100 dark:border-gray-800"><td class="py-1.5">Activity log rows</td><td class="py-1.5 text-right">{{ $data['activity_log_rows'] ?? '—' }}</td></tr>
            </tbody></table>
        </x-filament::section>
        <x-filament::section heading="Environment">
            <table class="w-full text-sm"><tbody>
                @foreach ($environment as $key => $value)
                    <tr class="border-t border-gray-100 dark:border-gray-800"><td class="py-1.5">{{ str_replace('_', ' ', ucfirst($key)) }}</td><td class="py-1.5 text-right text-xs">{{ $value }}</td></tr>
                @endforeach
            </tbody></table>
        </x-filament::section>
    </div>

    <x-filament::section heading="Recent automation failures">
        @if ($recentFailures->isEmpty())<p class="text-sm text-gray-500">No failed runs.</p>@else
        <table class="w-full text-sm"><thead><tr class="text-left text-gray-500"><th class="pb-2">When</th><th class="pb-2">Rule</th><th class="pb-2">Subject</th><th class="pb-2">Attempts</th><th class="pb-2">Error</th></tr></thead><tbody>
            @foreach ($recentFailures as $run)<tr class="border-t border-gray-100 dark:border-gray-800"><td class="py-1.5">{{ $run->finished_at?->format('d M H:i') }}</td><td class="py-1.5">{{ $run->rule?->name }}</td><td class="py-1.5">{{ $run->subject_type }} #{{ $run->subject_id }}</td><td class="py-1.5">{{ $run->attempts }}</td><td class="py-1.5 break-all text-xs">{{ $run->error }}</td></tr>@endforeach
        </tbody></table>@endif
    </x-filament::section>
</x-filament-panels::page>
