<footer data-theme="dark" class="relative overflow-hidden bg-canvas-dark bg-mesh-soft text-fg">
    @if ($cta && $ctaHref)
        <div class="border-b border-line">
            <x-ui.container class="flex flex-col gap-6 py-12 md:flex-row md:items-center md:justify-between">
                <div class="max-w-2xl">
                    <p class="text-h2">{{ $cta->headline ?? $cta->primary_label }}</p>
                    @if ($cta->body)
                        <p class="mt-2 text-body-lg text-fg-secondary">{{ $cta->body }}</p>
                    @endif
                </div>
                <div class="flex flex-wrap gap-3">
                    <x-ui.button :href="$ctaHref" size="lg">{{ $cta->primary_label }}</x-ui.button>
                    @if ($cta->secondary_label && $ctaSecondaryHref)
                        <x-ui.button :href="$ctaSecondaryHref" variant="outline" size="lg">{{ $cta->secondary_label }}</x-ui.button>
                    @endif
                </div>
            </x-ui.container>
        </div>
    @endif

    <x-ui.container class="py-14">
        <div class="grid gap-10 lg:grid-cols-[1.4fr_repeat({{ max(count($columns), 1) }},minmax(0,1fr))] lg:gap-8">
            <div class="max-w-sm">
                <x-layout.logo :company-name="$companyName" inverse />
                @if ($tagline)
                    <p class="mt-4 text-eyebrow text-brand">{{ $tagline }}</p>
                @endif
                @if ($description)
                    <p class="mt-3 text-body-sm text-fg-secondary">{{ $description }}</p>
                @endif
                <ul class="mt-6 space-y-2 text-body-sm text-fg-secondary">
                    @if ($email)
                        <li><a href="{{ $emailHref }}" class="hover:text-fg">{{ $email }}</a></li>
                    @endif
                    @if ($phone)
                        <li><a href="{{ $phoneHref }}" class="hover:text-fg">{{ $phone }}</a></li>
                    @endif
                    @if ($address)
                        <li class="whitespace-pre-line">{{ $address }}</li>
                    @endif
                </ul>
                @if ($social->isNotEmpty())
                    <ul class="mt-6 flex flex-wrap gap-3" aria-label="Social profiles">
                        @foreach ($social as $link)
                            <li>
                                <a href="{{ $link->url }}" target="_blank" rel="noopener" class="inline-flex h-9 items-center rounded-control border border-line px-3 text-caption text-fg-secondary hover:border-line-strong hover:text-fg">
                                    {{ $link->label ?: ucfirst($link->platform) }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            @foreach ($columns as $column)
                <div x-data="accordion()" class="border-t border-line pt-4 lg:border-0 lg:pt-0">
                    <button type="button" class="flex w-full items-center justify-between text-left text-eyebrow text-fg lg:hidden" @click="toggle('c')" :aria-expanded="isActive('c')">
                        {{ $column->label }}
                        <x-ui.icon name="heroicon-m-chevron-down" class="size-4 transition-transform" ::class="isActive('c') && 'rotate-180'" />
                    </button>
                    <p class="hidden text-eyebrow text-fg lg:block">{{ $column->label }}</p>
                    <ul class="mt-4 space-y-2.5 lg:!block" x-show="isActive('c')" x-collapse x-cloak>
                        @foreach ($column->children as $link)
                            <li><a href="{{ $link->url }}" class="text-body-sm text-fg-secondary hover:text-fg">{{ $link->label }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>

        <div class="mt-12 flex flex-col gap-4 border-t border-line pt-6 text-caption text-fg-muted md:flex-row md:items-center md:justify-between">
            <p>&copy; {{ now()->year }} {{ $companyName }}. All rights reserved.</p>
            @if ($legal)
                <ul class="flex flex-wrap gap-x-5 gap-y-2">
                    @foreach ($legal as $link)
                        <li><a href="{{ $link->url }}" class="hover:text-fg">{{ $link->label }}</a></li>
                    @endforeach
                </ul>
            @endif
        </div>
    </x-ui.container>
</footer>
