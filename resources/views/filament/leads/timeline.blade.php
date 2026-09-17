@php
    $entries = $getViewData()['entries'] ?? collect();
    $followUps = $getViewData()['followUps'] ?? collect();
@endphp
<div class="space-y-6">
    <div>
        <h4 class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-200">Follow-ups</h4>
        @if ($followUps->isEmpty())
            <p class="text-sm text-gray-500 dark:text-gray-400">No follow-ups scheduled.</p>
        @else
            <ul class="divide-y divide-gray-100 text-sm dark:divide-gray-800">
                @foreach ($followUps as $followUp)
                    <li class="flex flex-wrap items-center justify-between gap-2 py-2">
                        <span>
                            <span class="font-medium">{{ $followUp->type->getLabel() }}</span>
                            <span class="text-gray-500 dark:text-gray-400">· {{ $followUp->owner?->name ?? 'Unassigned' }}</span>
                            @if ($followUp->note)<span class="block text-gray-600 dark:text-gray-300">{{ $followUp->note }}</span>@endif
                        </span>
                        @if ($followUp->completed_at)
                            <x-filament::badge color="success">Done {{ $followUp->completed_at->format('d M Y H:i') }}</x-filament::badge>
                        @elseif ($followUp->isOverdue())
                            <x-filament::badge color="danger">Overdue · {{ $followUp->due_at->format('d M Y H:i') }}</x-filament::badge>
                        @else
                            <x-filament::badge color="info">Due {{ $followUp->due_at->format('d M Y H:i') }}</x-filament::badge>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
    <div>
        <h4 class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-200">Timeline</h4>
        <ol class="space-y-3 text-sm">
            @foreach ($entries as $entry)
                <li class="flex gap-3">
                    <span class="w-32 shrink-0 text-gray-500 dark:text-gray-400">{{ $entry['at']->format('d M Y H:i') }}</span>
                    <span>
                        <span class="font-medium">{{ $entry['title'] }}</span>
                        @if ($entry['actor'])<span class="text-gray-500 dark:text-gray-400">· {{ $entry['actor'] }}</span>@endif
                        @if ($entry['body'])<span class="block whitespace-pre-line text-gray-600 dark:text-gray-300">{{ $entry['body'] }}</span>@endif
                    </span>
                </li>
            @endforeach
        </ol>
    </div>
</div>
