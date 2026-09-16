<x-filament-panels::page>
    <x-filament::section heading="Filters" collapsible>
        <div class="grid gap-3 md:grid-cols-4">
            <label class="text-sm"><span class="block text-xs text-gray-500">Search title</span><input type="search" wire:model.live.debounce.400ms="search" class="fi-input mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900"></label>
            <label class="text-sm"><span class="block text-xs text-gray-500">Type</span>
                <select wire:model.live="type" class="fi-input mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900"><option value="">All types</option>@foreach ($types as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></label>
            <label class="text-sm"><span class="block text-xs text-gray-500">Status</span>
                <select wire:model.live="status" multiple class="fi-input mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900">@foreach ($statuses as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></label>
            <label class="text-sm"><span class="block text-xs text-gray-500">Indexability</span>
                <select wire:model.live="indexability" class="fi-input mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900"><option value="">Any</option><option value="indexable">Indexable</option><option value="noindex">Noindex</option><option value="canonicalized">Canonicalized elsewhere</option></select></label>
            <label class="text-sm"><span class="block text-xs text-gray-500">Owner</span>
                <select wire:model.live="owner" class="fi-input mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900"><option value="">Anyone</option>@foreach ($users as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</select></label>
            <label class="text-sm"><span class="block text-xs text-gray-500">Reviewer</span>
                <select wire:model.live="reviewer" class="fi-input mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900"><option value="">Anyone</option>@foreach ($users as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</select></label>
            <label class="text-sm"><span class="block text-xs text-gray-500">Author (created by)</span>
                <select wire:model.live="author" class="fi-input mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900"><option value="">Anyone</option>@foreach ($users as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</select></label>
            <label class="text-sm"><span class="block text-xs text-gray-500">Category slug</span><input type="text" wire:model.live.debounce.400ms="category" class="fi-input mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900" placeholder="e.g. technology"></label>
            <label class="text-sm"><span class="block text-xs text-gray-500">Published from</span><input type="date" wire:model.live="publishedFrom" class="fi-input mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900"></label>
            <label class="text-sm"><span class="block text-xs text-gray-500">Published until</span><input type="date" wire:model.live="publishedUntil" class="fi-input mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900"></label>
            <label class="text-sm"><span class="block text-xs text-gray-500">Modified from</span><input type="date" wire:model.live="updatedFrom" class="fi-input mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900"></label>
            <label class="text-sm"><span class="block text-xs text-gray-500">Modified until</span><input type="date" wire:model.live="updatedUntil" class="fi-input mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900"></label>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model.live="expired" class="rounded border-gray-300"> Expired only</label>
            <label class="text-sm"><span class="block text-xs text-gray-500">Sort</span>
                <select wire:model.live="sort" class="fi-input mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900"><option value="updated_at">Last modified</option><option value="published_at">Publish date</option><option value="submitted_at">Submitted</option><option value="unpublish_at">Unpublish date</option><option value="title">Title</option></select></label>
            <div class="flex items-end"><x-filament::button color="gray" size="sm" wire:click="resetFilters">Reset</x-filament::button></div>
        </div>
    </x-filament::section>

    <x-filament::section>
        <p class="mb-3 text-sm text-gray-500">{{ $rows->total() }} record(s)</p>
        @if ($rows->isEmpty())
            <p class="text-sm text-gray-500">Nothing matches these filters.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead><tr class="text-left text-gray-500">
                        <th class="pb-2">Title</th><th class="pb-2">Type</th><th class="pb-2">Status</th><th class="pb-2">Owner</th><th class="pb-2">Reviewer</th><th class="pb-2">Author</th>
                        @if ($queue)<th class="pb-2">Submitted</th><th class="pb-2">Scheduled for</th>@else<th class="pb-2">Published</th><th class="pb-2">Indexability</th>@endif
                        <th class="pb-2">Modified</th><th class="pb-2"></th>
                    </tr></thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr class="border-t border-gray-100 dark:border-gray-800" wire:key="row-{{ $row->type }}-{{ $row->id }}">
                                <td class="py-2 font-medium">{{ $row->title }}<span class="block text-xs font-normal text-gray-500">/{{ $row->slug }}</span></td>
                                <td class="py-2">{{ $types[$row->type] ?? $row->type }}</td>
                                <td class="py-2"><x-filament::badge :color="\App\Enums\PublishStatus::from($row->status)->getColor()" size="sm">{{ \App\Enums\PublishStatus::from($row->status)->getLabel() }}</x-filament::badge>@if ($row->approved_at) <span class="text-xs text-success-600">approved</span>@endif</td>
                                <td class="py-2">{{ $names[$row->owner_id] ?? '—' }}</td>
                                <td class="py-2">{{ $names[$row->reviewer_id] ?? '—' }}</td>
                                <td class="py-2">{{ $names[$row->created_by] ?? '—' }}</td>
                                @if ($queue)
                                    <td class="py-2">{{ $row->submitted_at ? \Illuminate\Support\Carbon::parse($row->submitted_at)->format('d M Y H:i') : '—' }}</td>
                                    <td class="py-2">{{ $row->status === 'scheduled' && $row->published_at ? \Illuminate\Support\Carbon::parse($row->published_at)->format('d M Y H:i') : '—' }}</td>
                                @else
                                    <td class="py-2">{{ $row->published_at ? \Illuminate\Support\Carbon::parse($row->published_at)->format('d M Y') : '—' }}@if ($row->unpublish_at)<span class="block text-xs text-gray-500">until {{ \Illuminate\Support\Carbon::parse($row->unpublish_at)->format('d M Y') }}</span>@endif</td>
                                    <td class="py-2">{{ \App\Filament\Pages\ContentInventory::indexabilityLabel($row) }}</td>
                                @endif
                                <td class="py-2">{{ \Illuminate\Support\Carbon::parse($row->updated_at)->diffForHumans() }}</td>
                                <td class="py-2 text-right whitespace-nowrap">
                                    @if ($url = $this->editUrl($row->type, $row->id))<a href="{{ $url }}" class="text-primary-600 hover:underline">Edit</a> ·@endif
                                    <a href="{{ $this->deskUrl($row->type, $row->id) }}" class="text-primary-600 hover:underline">Desk</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $rows->links() }}</div>
        @endif
    </x-filament::section>
</x-filament-panels::page>
