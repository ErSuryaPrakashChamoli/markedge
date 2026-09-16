<x-filament-panels::page>
    <div class="grid gap-4 md:grid-cols-4">
        <x-filament::section heading="Status">
            <x-filament::badge :color="$record->status->getColor()">{{ $record->status->getLabel() }}</x-filament::badge>
            @if ($record->isApproved())
                <p class="mt-2 text-xs text-success-600">Approved by {{ $record->approver?->name ?? 'someone' }} {{ $record->approved_at->diffForHumans() }}</p>
            @elseif ($record->status->value === 'review')
                <p class="mt-2 text-xs text-gray-500">Submitted {{ $record->submitted_at?->diffForHumans() ?? '—' }}, awaiting approval</p>
            @endif
        </x-filament::section>
        <x-filament::section heading="People">
            <dl class="space-y-1 text-sm">
                <div><dt class="inline text-gray-500">Owner:</dt> <dd class="inline">{{ $record->owner?->name ?? 'Unassigned' }}</dd></div>
                <div><dt class="inline text-gray-500">Reviewer:</dt> <dd class="inline">{{ $record->reviewer?->name ?? 'Unassigned' }}</dd></div>
                <div><dt class="inline text-gray-500">Created by:</dt> <dd class="inline">{{ $record->creator?->name ?? '—' }}</dd></div>
                <div><dt class="inline text-gray-500">Last edited by:</dt> <dd class="inline">{{ $record->editor?->name ?? '—' }}</dd></div>
            </dl>
        </x-filament::section>
        <x-filament::section heading="Dates">
            <dl class="space-y-1 text-sm">
                <div><dt class="inline text-gray-500">Publish:</dt> <dd class="inline">{{ $record->published_at?->format('d M Y H:i') ?? '—' }}</dd></div>
                <div><dt class="inline text-gray-500">Unpublish:</dt> <dd class="inline {{ $record->hasExpired() ? 'text-danger-600' : '' }}">{{ $record->unpublish_at?->format('d M Y H:i') ?? '—' }}{{ $record->hasExpired() ? ' (passed)' : '' }}</dd></div>
                <div><dt class="inline text-gray-500">Modified:</dt> <dd class="inline">{{ $record->updated_at?->diffForHumans() }}</dd></div>
            </dl>
        </x-filament::section>
        <x-filament::section heading="Versions">
            <p class="text-3xl font-semibold">v{{ $latestVersion }}</p>
            <p class="text-xs text-gray-500">current version</p>
        </x-filament::section>
    </div>

    @if ($changeRequest)
        <x-filament::section heading="Changes requested" icon="heroicon-o-exclamation-triangle" icon-color="warning">
            <p class="text-sm">{{ $changeRequest->body }}</p>
            <p class="mt-1 text-xs text-gray-500">{{ $changeRequest->user?->name ?? 'Reviewer' }}, {{ $changeRequest->created_at->diffForHumans() }}</p>
        </x-filament::section>
    @endif

    <div class="grid gap-6 lg:grid-cols-2">
        <x-filament::section heading="Editorial comments" description="Internal only. Never shown on the website.">
            @if ($canComment)
                <form wire:submit="addComment" class="mb-4 space-y-2">
                    <label for="comment-body" class="sr-only">Comment</label>
                    <textarea id="comment-body" wire:model="commentBody" rows="3" class="fi-input w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900" placeholder="Add a note for the team"></textarea>
                    @error('commentBody')<p class="text-xs text-danger-600">{{ $message }}</p>@enderror
                    <x-filament::button type="submit" size="sm">Add comment</x-filament::button>
                </form>
            @endif
            @if ($comments->isEmpty())
                <p class="text-sm text-gray-500">No comments yet.</p>
            @else
                <ul class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach ($comments as $comment)
                        <li class="py-3 text-sm" wire:key="comment-{{ $comment->id }}">
                            <div class="flex items-center gap-2">
                                <x-filament::badge :color="$comment->type->getColor()" size="sm">{{ $comment->type->getLabel() }}</x-filament::badge>
                                <span class="font-medium">{{ $comment->user?->name ?? 'Unknown user' }}</span>
                                <span class="text-xs text-gray-500">{{ $comment->created_at->diffForHumans() }}</span>
                                @if ($comment->isResolved())
                                    <span class="text-xs text-success-600">resolved by {{ $comment->resolver?->name ?? '—' }}</span>
                                @elseif ($canReview || $comment->user_id === auth()->id())
                                    <button type="button" wire:click="resolveComment({{ $comment->id }})" class="ml-auto text-xs text-primary-600 hover:underline">Mark resolved</button>
                                @endif
                            </div>
                            <p class="mt-1 whitespace-pre-line">{{ $comment->body }}</p>
                        </li>
                    @endforeach
                </ul>
                {{ $comments->links() }}
            @endif
        </x-filament::section>

        <x-filament::section heading="Version history" description="Every content change is kept. Restoring creates a new version; history is never overwritten.">
            @if ($revisions->isEmpty())
                <p class="text-sm text-gray-500">No versions yet. The first save creates v1.</p>
            @else
                <table class="w-full text-sm">
                    <thead><tr class="text-left text-gray-500"><th class="pb-2">Version</th><th class="pb-2">Author</th><th class="pb-2">When</th><th class="pb-2">Reason</th><th class="pb-2"></th></tr></thead>
                    <tbody>
                        @foreach ($revisions as $revision)
                            <tr class="border-t border-gray-100 dark:border-gray-800" wire:key="revision-{{ $revision->id }}">
                                <td class="py-2 font-medium">v{{ $revision->version }} @if ($revision->version === $latestVersion)<x-filament::badge color="success" size="sm">current</x-filament::badge>@endif</td>
                                <td class="py-2">{{ $revision->author?->name ?? 'System' }}</td>
                                <td class="py-2">{{ $revision->created_at->format('d M Y H:i') }}</td>
                                <td class="py-2 text-gray-500">{{ $revision->reason ?? '—' }}</td>
                                <td class="py-2 text-right whitespace-nowrap">
                                    @if ($revision->version !== $latestVersion)
                                        {{ ($this->compareAction)(['revision' => $revision->id, 'version' => $revision->version]) }}
                                        @if ($canRestore)
                                            {{ ($this->restoreAction)(['revision' => $revision->id, 'version' => $revision->version, 'latest' => $latestVersion]) }}
                                        @endif
                                    @endif
                                    {{ ($this->rawAction)(['revision' => $revision->id]) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                {{ $revisions->links() }}
            @endif
        </x-filament::section>
    </div>

    <x-filament::section heading="Lifecycle" description="From the audit trail: who did what, when.">
        @if ($activity->isEmpty())
            <p class="text-sm text-gray-500">No activity recorded yet.</p>
        @else
            <ul class="divide-y divide-gray-100 text-sm dark:divide-gray-800">
                @foreach ($activity as $entry)
                    <li class="flex flex-wrap items-center gap-2 py-2">
                        <span class="font-medium">{{ ucfirst($entry->event ?? $entry->description) }}</span>
                        <span class="text-gray-500">by {{ $entry->causer?->name ?? 'system' }}</span>
                        <span class="text-xs text-gray-500">{{ $entry->created_at->format('d M Y H:i') }}</span>
                        @if (($entry->properties['from'] ?? null) && ($entry->properties['to'] ?? null))
                            <span class="text-xs text-gray-500">{{ $entry->properties['from'] }} → {{ $entry->properties['to'] }}</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </x-filament::section>

    <x-filament-actions::modals />
</x-filament-panels::page>
