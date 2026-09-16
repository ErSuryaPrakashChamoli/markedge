@if ($announcement)
    <div
        x-data="{ dismissed: false }"
        x-show="! dismissed"
        data-theme="dark"
        class="bg-canvas-dark text-fg"
        role="region"
        aria-label="Announcement"
    >
        <x-ui.container class="flex items-center justify-between gap-4 py-2.5">
            <p class="text-body-sm">
                {{ $announcement->message }}
                @if ($announcement->link_url && $announcement->link_label)
                    <a href="{{ $announcement->link_url }}" class="ml-2 font-semibold text-brand underline-offset-4 hover:underline">{{ $announcement->link_label }}</a>
                @endif
            </p>
            @if ($announcement->is_dismissible)
                <button type="button" @click="dismissed = true" class="shrink-0 rounded-control p-1 text-fg-muted hover:text-fg" aria-label="Dismiss announcement">
                    <x-ui.icon name="heroicon-o-x-mark" class="size-4" />
                </button>
            @endif
        </x-ui.container>
    </div>
@endif
