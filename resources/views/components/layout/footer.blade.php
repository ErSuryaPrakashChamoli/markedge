{{--
    Four-section footer: brand & social, the footer menu columns (Company, Services), and a contact block
    fed by settings. Menu columns collapse into accordions on mobile.
--}}
<footer data-theme="dark" class="relative overflow-hidden bg-canvas-dark bg-mesh-soft text-fg">
    <div class="absolute inset-x-0 top-0 h-px bg-linear-to-r from-transparent via-brand to-transparent" aria-hidden="true"></div>
    <div class="pointer-events-none absolute -right-24 -top-32 size-[28rem] rounded-full bg-brand/10 blur-3xl" aria-hidden="true"></div>

    <x-ui.container class="relative pt-16 pb-10">
        <div class="grid gap-12 md:grid-cols-2 lg:grid-cols-[1.5fr_1fr_1fr_1.3fr] lg:gap-10">
            {{-- 1. Brand --}}
            <div class="max-w-sm md:col-span-2 lg:col-span-1">
                <x-layout.logo :company-name="$companyName" inverse />
                @if ($tagline)
                    <p class="mt-5 text-eyebrow text-brand">{{ $tagline }}</p>
                @endif
                @if ($description)
                    <p class="mt-3 text-body-sm text-fg-secondary">{{ $description }}</p>
                @endif
                @if ($social->isNotEmpty())
                    <ul class="mt-7 flex flex-wrap gap-2.5" aria-label="Social profiles">
                        @foreach ($social as $link)
                            <li>
                                <a href="{{ $link->url }}" target="_blank" rel="noopener" class="group inline-flex size-10 items-center justify-center rounded-control border border-line bg-surface/40 text-fg-secondary transition-all duration-300 hover:-translate-y-0.5 hover:border-brand hover:bg-brand hover:text-white hover:shadow-glow" aria-label="{{ $link->label ?: ucfirst($link->platform) }}">
                                    <x-ui.social-icon :platform="$link->platform" class="size-4" />
                                    <span class="sr-only">{{ $link->label ?: ucfirst($link->platform) }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            {{-- 2 & 3. Link columns from the footer menu --}}
            @foreach ($columns as $column)
                {{-- Open by default at desktop width; the collapse plugin would otherwise pin the list to zero height. --}}
                <div x-data="accordion(window.matchMedia('(min-width: 64rem)').matches ? 'c' : null)" class="border-t border-line pt-4 lg:border-0 lg:pt-0">
                    <span class="mb-4 hidden h-0.5 w-8 rounded-full bg-brand lg:block" aria-hidden="true"></span>
                    {{-- Column titles link to their section page when the menu item has a URL or linked content; plain headings only toggle on mobile. --}}
                    @if ($column->url)
                        <div class="flex items-center justify-between gap-3 lg:block">
                            <a href="{{ $column->url }}" class="py-1 text-eyebrow text-fg transition-colors hover:text-brand">{{ $column->label }}</a>
                            @if ($column->hasChildren())
                                <button type="button" class="-mr-2 inline-flex size-9 items-center justify-center rounded-control text-fg-secondary hover:text-fg lg:hidden" @click="toggle('c')" :aria-expanded="isActive('c')" aria-label="Show {{ $column->label }} links">
                                    <x-ui.icon name="heroicon-m-chevron-down" class="size-4 transition-transform" ::class="isActive('c') && 'rotate-180'" />
                                </button>
                            @endif
                        </div>
                    @else
                        <button type="button" class="flex w-full items-center justify-between py-1 text-left text-eyebrow text-fg lg:hidden" @click="toggle('c')" :aria-expanded="isActive('c')">
                            {{ $column->label }}
                            <x-ui.icon name="heroicon-m-chevron-down" class="size-4 transition-transform" ::class="isActive('c') && 'rotate-180'" />
                        </button>
                        <p class="hidden py-1 text-eyebrow text-fg lg:block">{{ $column->label }}</p>
                    @endif
                    @if ($column->hasChildren())
                        <ul class="mt-4 space-y-3 lg:mt-5" x-show="isActive('c')" x-collapse x-cloak>
                            @foreach ($column->children as $link)
                                <li>
                                    <a href="{{ $link->url }}" class="group relative inline-block text-body-sm text-fg-secondary transition-all duration-300 hover:pl-5 hover:text-fg" @if ($link->openInNewTab) target="_blank" rel="noopener" @endif>
                                        <span class="absolute top-1/2 left-0 h-px w-0 bg-brand transition-all duration-300 group-hover:w-3" aria-hidden="true"></span>
                                        {{ $link->label }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endforeach

            {{-- 4. Contact --}}
            <div class="border-t border-line pt-4 md:col-span-2 lg:col-span-1 lg:border-0 lg:pt-0">
                <span class="mb-4 hidden h-0.5 w-8 rounded-full bg-brand lg:block" aria-hidden="true"></span>
                <p class="py-1 text-eyebrow text-fg">Get in touch</p>
                <p class="mt-4 text-body-sm text-fg-secondary">Tell us what you're building. We reply within one business day.</p>
                <ul class="mt-5 space-y-3">
                    @if ($email)
                        <li>
                            <a href="{{ $emailHref }}" class="group flex items-start gap-3 text-body-sm text-fg-secondary transition-colors hover:text-fg">
                                <span class="mt-0.5 inline-flex size-8 shrink-0 items-center justify-center rounded-control bg-brand-soft text-brand transition-colors group-hover:bg-brand group-hover:text-white">
                                    <x-ui.icon name="heroicon-o-envelope" class="size-4" />
                                </span>
                                <span class="pt-1.5">{{ $email }}</span>
                            </a>
                        </li>
                    @endif
                    @if ($phone)
                        <li>
                            <a href="{{ $phoneHref }}" class="group flex items-start gap-3 text-body-sm text-fg-secondary transition-colors hover:text-fg">
                                <span class="mt-0.5 inline-flex size-8 shrink-0 items-center justify-center rounded-control bg-brand-soft text-brand transition-colors group-hover:bg-brand group-hover:text-white">
                                    <x-ui.icon name="heroicon-o-phone" class="size-4" />
                                </span>
                                <span class="pt-1.5">{{ $phone }}</span>
                            </a>
                        </li>
                    @endif
                    @if ($address)
                        <li class="flex items-start gap-3 text-body-sm text-fg-secondary">
                            <span class="mt-0.5 inline-flex size-8 shrink-0 items-center justify-center rounded-control bg-brand-soft text-brand">
                                <x-ui.icon name="heroicon-o-map-pin" class="size-4" />
                            </span>
                            <span class="whitespace-pre-line pt-1.5">{{ $address }}</span>
                        </li>
                    @endif
                </ul>
                <div class="mt-6 flex flex-wrap gap-3">
                    <x-ui.button :href="$contactUrl" size="sm" icon="heroicon-m-arrow-right">Contact us</x-ui.button>
                    @if ($whatsappHref)
                        <x-ui.button :href="$whatsappHref" variant="outline" size="sm" target="_blank" rel="noopener">
                            <x-ui.social-icon platform="whatsapp" class="size-4" />
                            WhatsApp
                        </x-ui.button>
                    @endif
                </div>
            </div>
        </div>

        <div class="mt-14 flex flex-col gap-4 border-t border-line pt-6 text-caption text-fg-muted md:flex-row md:items-center md:justify-between">
            <p>&copy; {{ now()->year }} {{ $companyName }}. All rights reserved.</p>
            @if ($legal)
                <ul class="flex flex-wrap gap-x-5 gap-y-2">
                    @foreach ($legal as $link)
                        <li><a href="{{ $link->url }}" class="transition-colors hover:text-fg">{{ $link->label }}</a></li>
                    @endforeach
                </ul>
            @endif
        </div>
    </x-ui.container>
</footer>
