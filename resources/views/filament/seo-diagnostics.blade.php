@php
    $colours = ['pass' => 'text-success-600', 'warn' => 'text-warning-600', 'fail' => 'text-danger-600', 'note' => 'text-gray-500'];
    $icons = ['pass' => 'heroicon-m-check-circle', 'warn' => 'heroicon-m-exclamation-triangle', 'fail' => 'heroicon-m-x-circle', 'note' => 'heroicon-m-information-circle'];
@endphp
<div class="space-y-4">
    @foreach (['required' => 'Required', 'recommended' => 'Recommended', 'informational' => 'Informational'] as $level => $heading)
        @php $group = array_filter($checks, fn ($check) => $check->level === $level); @endphp
        @if ($group !== [])
            <div>
                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $heading }}</p>
                <ul class="divide-y divide-gray-100 rounded-lg border border-gray-200 dark:divide-gray-800 dark:border-gray-700">
                    @foreach ($group as $check)
                        <li class="flex items-start gap-3 px-3 py-2 text-sm">
                            <x-filament::icon :icon="$icons[$check->status]" @class(['mt-0.5 size-5 shrink-0', $colours[$check->status]]) />
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">{{ $check->label }}</p>
                                @if ($check->detail)
                                    <p class="text-gray-600 dark:text-gray-400">{{ $check->detail }}</p>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    @endforeach
</div>
