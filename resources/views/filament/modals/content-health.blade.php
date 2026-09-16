<div class="space-y-3">
    @if ($issues === [])
        <p class="text-sm text-success-600">No issues found. Title, summary, blocks, links, CTA and indexability all check out.</p>
    @else
        <ul class="space-y-2">
            @foreach ($issues as $issue)
                <li class="flex items-start gap-3 text-sm">
                    <x-filament::badge :color="match ($issue['severity']) { 'error' => 'danger', 'warning' => 'warning', default => 'gray' }">{{ $issue['severity'] }}</x-filament::badge>
                    <span>{{ $issue['message'] }}</span>
                </li>
            @endforeach
        </ul>
    @endif
    <p class="text-xs text-gray-500 dark:text-gray-400">Diagnostics are factual checks, not a score. Fix the errors before publishing; warnings and notes are advisory.</p>
</div>
