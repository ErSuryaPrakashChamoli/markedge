@props(['data', 'host' => null, 'preview' => false])
{{--
    Grievance escalation ladder: an optional note (how to complain, timelines), then each level in order, how quickly
    it responds, and the people to contact there.
    Defaults to the #grievance-redressal anchor so footer and menu links can jump straight to it.
--}}
<x-ui.section :theme="$data['theme'] ?? 'light'" :id="filled($data['anchor'] ?? null) ? $data['anchor'] : 'grievance-redressal'">
    @if (filled($data['heading'] ?? null))
        <x-ui.section-header :title="$data['heading']" :intro="$data['intro'] ?? null" class="mb-10" />
    @endif
    @if ($data['hasNote'])
        <x-ui.prose :html="$data['note']" @class(['max-w-3xl', 'mb-12' => $data['levels']->isNotEmpty()]) />
    @endif
    @if ($data['levels']->isNotEmpty())
        <ol class="space-y-6">
            @foreach ($data['levels'] as $level)
                <li class="relative pl-14">
                    @unless ($loop->last)
                        <span class="absolute top-12 -bottom-6 left-5 w-px bg-line" aria-hidden="true"></span>
                    @endunless
                    <span class="absolute top-0 left-0 flex size-10 items-center justify-center rounded-pill bg-brand text-meta font-bold text-white" aria-hidden="true">{{ $loop->iteration }}</span>
                    <x-ui.card>
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="text-eyebrow text-brand">Level {{ $loop->iteration }}</p>
                                <h3 class="mt-1 text-h4">{{ $level->name }}</h3>
                            </div>
                            @if ($level->response_time)
                                <x-ui.badge tone="brand" class="gap-1.5">
                                    <x-ui.icon name="heroicon-m-clock" class="size-3.5" />
                                    {{ $level->response_time }}
                                </x-ui.badge>
                            @endif
                        </div>
                        @if ($level->description)
                            <p class="mt-3 whitespace-pre-line text-body-sm text-fg-secondary">{{ $level->description }}</p>
                        @endif
                        @if ($level->contacts->isNotEmpty())
                            <ul class="mt-5 grid gap-4 md:grid-cols-2">
                                @foreach ($level->contacts as $contact)
                                    <li class="rounded-control border border-line bg-canvas-muted/40 p-4">
                                        <p class="font-semibold text-fg">{{ $contact->name }}</p>
                                        @if ($contact->designation)
                                            <p class="text-caption text-fg-muted">{{ $contact->designation }}</p>
                                        @endif
                                        <ul class="mt-3 space-y-2 text-body-sm text-fg-secondary">
                                            @if ($contact->email)
                                                <li class="flex items-start gap-2.5">
                                                    <x-ui.icon name="heroicon-o-envelope" class="mt-0.5 size-4 text-brand" />
                                                    <a href="mailto:{{ $contact->email }}" class="break-all transition-colors hover:text-brand">{{ $contact->email }}</a>
                                                </li>
                                            @endif
                                            @if ($contact->phone)
                                                <li class="flex items-start gap-2.5">
                                                    <x-ui.icon name="heroicon-o-phone" class="mt-0.5 size-4 text-brand" />
                                                    <a href="{{ $contact->phoneHref() }}" class="transition-colors hover:text-brand">{{ $contact->phone }}</a>
                                                </li>
                                            @endif
                                            @if ($contact->address)
                                                <li class="flex items-start gap-2.5">
                                                    <x-ui.icon name="heroicon-o-map-pin" class="mt-0.5 size-4 text-brand" />
                                                    <span class="whitespace-pre-line">{{ $contact->address }}</span>
                                                </li>
                                            @endif
                                        </ul>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </x-ui.card>
                </li>
            @endforeach
        </ol>
    @endif
</x-ui.section>
