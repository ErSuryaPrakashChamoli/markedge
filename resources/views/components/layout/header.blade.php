<header
    x-data="siteNav"
    @keydown.escape.window="closeAll()"
    class="sticky top-0 z-50 border-b border-line bg-surface/95 backdrop-blur supports-[backdrop-filter]:bg-surface/85"
>
    <x-ui.container class="flex h-16 items-center justify-between gap-6 lg:h-[4.5rem]">
        <x-layout.logo :company-name="$companyName" :logo-url="$logoUrl" />

        @unless ($minimal)
            <x-navigation.desktop :items="$items" />
        @endunless

        <div class="flex items-center gap-3">
            <a href="{{ route('search') }}" class="inline-flex size-10 items-center justify-center rounded-control text-fg-secondary hover:text-fg" aria-label="Search the site">
                <x-ui.icon name="heroicon-o-magnifying-glass" class="size-5" />
            </a>
            @if ($cta && $ctaHref)
                <x-ui.button :href="$ctaHref" size="sm" class="hidden sm:inline-flex">{{ $cta->primary_label }}</x-ui.button>
            @endif

            @if ($phoneHref)
                <a href="{{ $phoneHref }}" class="inline-flex size-10 items-center justify-center rounded-control text-fg-secondary hover:text-fg lg:hidden" aria-label="Call us">
                    <x-ui.icon name="heroicon-o-phone" class="size-5" />
                </a>
            @endif

            @unless ($minimal)
                <button
                    type="button"
                    @click="toggleDrawer()"
                    :aria-expanded="drawerOpen"
                    aria-controls="mobile-nav"
                    class="inline-flex size-10 items-center justify-center rounded-control text-fg lg:hidden"
                    aria-label="Open menu"
                >
                    <x-ui.icon name="heroicon-o-bars-3" class="size-6" x-show="! drawerOpen" />
                    <x-ui.icon name="heroicon-o-x-mark" class="size-6" x-show="drawerOpen" x-cloak />
                </button>
            @endunless
        </div>
    </x-ui.container>

    @unless ($minimal)
        <x-navigation.mobile :items="$items" :cta="$cta" :cta-href="$ctaHref" :phone-href="$phoneHref" :whatsapp-href="$whatsappHref" />
    @endunless
</header>
