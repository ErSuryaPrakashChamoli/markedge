<x-filament-panels::page>
    <div class="flex flex-wrap items-end gap-3 text-sm">
        <label class="flex flex-col gap-1">
            <span class="text-xs font-medium text-gray-500">Owner</span>
            <select wire:model.live="owner" class="fi-input rounded-lg border border-solid border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900">
                <option value="all">Everyone</option>
                <option value="mine">My leads</option>
                <option value="unassigned">Unassigned</option>
                @foreach ($owners as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach
            </select>
        </label>
        @if ($teams !== [])
            <label class="flex flex-col gap-1">
                <span class="text-xs font-medium text-gray-500">Team</span>
                <select wire:model.live="team" class="fi-input rounded-lg border border-solid border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900">
                    <option value="">All teams</option>
                    @foreach ($teams as $name)<option value="{{ $name }}">{{ $name }}</option>@endforeach
                </select>
            </label>
        @endif
        <label class="flex flex-col gap-1">
            <span class="text-xs font-medium text-gray-500">Priority</span>
            <select wire:model.live="priority" class="fi-input rounded-lg border border-solid border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900">
                <option value="">Any</option>
                @foreach ($priorities as $case)<option value="{{ $case->value }}">{{ $case->getLabel() }}</option>@endforeach
            </select>
        </label>
        <span class="ml-auto text-xs text-gray-500">First-contact SLA: {{ $slaHours === null ? 'not configured' : $slaHours.' h' }}. Columns show up to {{ $limit }} leads, urgent first, then longest in stage.</span>
    </div>

    <div style="overflow-x: auto; padding-bottom: .5rem;">
        <div style="display: grid; grid-template-columns: repeat(7, minmax(210px, 1fr)); gap: .75rem; min-width: 1470px;">
            @foreach ($columns as $column)
                <section class="rounded-xl bg-gray-50 p-3 dark:bg-gray-900/60" aria-label="{{ $column['status']->getLabel() }}">
                    <header class="mb-3 flex items-center justify-between">
                        <h3 class="text-sm font-semibold">{{ $column['status']->getLabel() }}</h3>
                        <x-filament::badge :color="$column['status']->getColor()">{{ $column['count'] }}</x-filament::badge>
                    </header>
                    <div class="space-y-2">
                        @forelse ($column['leads'] as $lead)
                            <a href="{{ \App\Filament\Resources\Leads\LeadResource::getUrl('view', ['record' => $lead]) }}" class="block rounded-lg border border-gray-200 bg-white p-3 text-sm shadow-sm transition hover:border-primary-400 dark:border-gray-700 dark:bg-gray-800">
                                <div class="flex items-start justify-between gap-2">
                                    <span class="font-medium">{{ $lead->name }}</span>
                                    @if ($lead->priority !== \App\Enums\LeadPriority::Normal)
                                        <x-filament::badge size="sm" :color="$lead->priority->getColor()">{{ $lead->priority->getLabel() }}</x-filament::badge>
                                    @endif
                                </div>
                                @if ($lead->company)<p class="text-xs text-gray-500 dark:text-gray-400">{{ $lead->company }}</p>@endif
                                <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">{{ $lead->product?->name ?? $lead->service?->name ?? 'General enquiry' }}</p>
                                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ $lead->assignee?->name ?? 'Unassigned' }} · {{ (int) ($lead->stage_entered_at ?? $lead->created_at)->diffInDays(now()) }} d in stage</p>
                                @if ($lead->next_follow_up_at || $lead->breachesFirstContactSla())
                                    <p class="mt-1 flex flex-wrap gap-1">
                                        @if ($lead->next_follow_up_at)
                                            <x-filament::badge size="sm" :color="$lead->next_follow_up_at->isPast() ? 'danger' : 'gray'">Follow-up {{ $lead->next_follow_up_at->format('d M H:i') }}</x-filament::badge>
                                        @endif
                                        @if ($lead->breachesFirstContactSla())<x-filament::badge size="sm" color="danger">SLA breached</x-filament::badge>@endif
                                    </p>
                                @endif
                            </a>
                        @empty
                            <p class="text-xs text-gray-400">Empty</p>
                        @endforelse
                        @if ($column['count'] > $column['leads']->count())
                            <p class="text-xs text-gray-500">+{{ $column['count'] - $column['leads']->count() }} more in the enquiries list.</p>
                        @endif
                    </div>
                </section>
            @endforeach
        </div>
    </div>
</x-filament-panels::page>
