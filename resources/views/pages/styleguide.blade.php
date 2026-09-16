<x-layouts.app title="Styleguide" robots="noindex, nofollow">
    <x-ui.section spacing="sm">
        <x-ui.section-header eyebrow="Design system" title="Markedge styleguide" intro="Every public page is composed from these primitives. Nothing here is editable by content administrators." />
    </x-ui.section>

    <x-ui.section spacing="sm" id="colours">
        <h2 class="text-h3">Semantic colours</h2>
        <div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-6">
            @foreach (['canvas', 'canvas-muted', 'canvas-dark', 'surface', 'surface-elevated', 'brand', 'brand-hover', 'brand-soft', 'brand-secondary', 'success', 'warning', 'danger', 'info', 'line', 'line-strong'] as $token)
                <div class="rounded-card border border-line p-3">
                    <div class="h-12 rounded-control border border-line bg-{{ $token }}"></div>
                    <p class="mt-2 text-meta text-fg-secondary">{{ $token }}</p>
                </div>
            @endforeach
        </div>
    </x-ui.section>

    <x-ui.section spacing="sm" id="type">
        <h2 class="text-h3">Typography</h2>
        <div class="mt-6 space-y-5">
            @foreach (['display', 'h1', 'h2', 'h3', 'h4', 'body-lg', 'body', 'body-sm', 'eyebrow', 'nav', 'button', 'caption', 'meta'] as $style)
                <div class="grid gap-2 border-b border-line pb-4 md:grid-cols-[8rem_1fr]">
                    <p class="text-meta text-fg-muted">text-{{ $style }}</p>
                    <p class="text-{{ $style }}">Technology that moves business forward.</p>
                </div>
            @endforeach
        </div>
    </x-ui.section>

    <x-ui.section spacing="sm" id="buttons">
        <h2 class="text-h3">Buttons</h2>
        <div class="mt-6 flex flex-wrap items-center gap-3">
            <x-ui.button>Primary</x-ui.button>
            <x-ui.button variant="secondary">Secondary</x-ui.button>
            <x-ui.button variant="outline">Outline</x-ui.button>
            <x-ui.button variant="ghost">Ghost</x-ui.button>
            <x-ui.button variant="link">Link</x-ui.button>
            <x-ui.button size="sm">Small</x-ui.button>
            <x-ui.button size="lg" icon="heroicon-m-arrow-right">Large with icon</x-ui.button>
            <x-ui.button disabled>Disabled</x-ui.button>
        </div>
        <div class="mt-6 flex flex-wrap gap-3">
            <x-ui.badge>Neutral</x-ui.badge>
            <x-ui.badge tone="brand">Brand</x-ui.badge>
            <x-ui.badge tone="success">Success</x-ui.badge>
            <x-ui.badge tone="warning">Warning</x-ui.badge>
            <x-ui.badge tone="danger">Danger</x-ui.badge>
            <x-ui.badge tone="info">Info</x-ui.badge>
        </div>
    </x-ui.section>

    <x-ui.section spacing="sm" theme="neutral" id="cards">
        <h2 class="text-h3">Cards and grids</h2>
        <x-ui.grid cols="3" class="mt-6">
            @foreach (range(1, 3) as $i)
                <x-ui.card href="#cards">
                    <x-ui.picture class="mb-5" ratio="aspect-[16/9]" placeholder-label="Image {{ $i }}" />
                    <x-ui.badge tone="brand" class="self-start">Service</x-ui.badge>
                    <h3 class="mt-3 text-h4">Card title {{ $i }}</h3>
                    <p class="mt-2 text-body-sm text-fg-secondary">Cards are flat, bordered surfaces. The border turns orange on hover and focus.</p>
                    <span class="mt-4 inline-flex items-center gap-1.5 text-button text-fg group-hover:text-brand">Learn more <x-ui.icon name="heroicon-m-arrow-right" class="size-4" /></span>
                </x-ui.card>
            @endforeach
        </x-ui.grid>
        <x-ui.grid cols="4" class="mt-6">
            @foreach (range(1, 4) as $i)
                <x-ui.card :padding="false" class="p-4"><p class="text-body-sm">Four-column item {{ $i }}</p></x-ui.card>
            @endforeach
        </x-ui.grid>
    </x-ui.section>

    <x-ui.section spacing="sm" theme="dark" pattern="grid" id="dark">
        <x-ui.split ratio="wide-left">
            <x-slot:left>
                <x-ui.section-header eyebrow="Dark section" title="Tokens flip automatically inside dark sections" intro="Text, borders, surfaces and buttons all read the same semantic tokens, so components need no dark variants." />
                <div class="mt-6 flex gap-3">
                    <x-ui.button>Primary</x-ui.button>
                    <x-ui.button variant="secondary">Secondary</x-ui.button>
                    <x-ui.button variant="outline">Outline</x-ui.button>
                </div>
            </x-slot:left>
            <x-slot:right>
                <x-ui.card>
                    <x-ui.stat value="24" label="Services in the catalogue" note="Example of an animated numeric stat" />
                    <x-ui.divider class="my-5" />
                    <x-ui.stat value="Eliminated" label="Manual reporting" note="Qualitative outcomes render as text" />
                </x-ui.card>
            </x-slot:right>
        </x-ui.split>
    </x-ui.section>

    <x-ui.section spacing="sm" id="forms" container="narrow">
        <h2 class="text-h3">Form controls</h2>
        <form class="mt-6" onsubmit="return false">
            <x-forms.grid>
                <x-forms.field id="sg-name" label="Name" required width="half"><x-forms.input id="sg-name" placeholder="Your name" /></x-forms.field>
                <x-forms.field id="sg-email" label="Email" required width="half" error="Please enter a valid email address."><x-forms.input id="sg-email" type="email" invalid /></x-forms.field>
                <x-forms.field id="sg-service" label="Area of interest" help="Optional"><x-forms.select id="sg-service" placeholder="Select an option"><option>Software Development</option></x-forms.select></x-forms.field>
                <x-forms.field id="sg-message" label="Message"><x-forms.textarea id="sg-message" /></x-forms.field>
                <div class="md:col-span-2"><x-forms.checkbox id="sg-consent" label="I agree to be contacted about my enquiry." /></div>
                <div class="md:col-span-2"><x-ui.button type="submit">Send enquiry</x-ui.button></div>
            </x-forms.grid>
        </form>
    </x-ui.section>

    <x-ui.section spacing="sm" id="prose" container="prose">
        <x-layout.breadcrumbs :items="[['label' => 'Services', 'url' => '/services'], ['label' => 'Software Development']]" class="mb-6" />
        <x-ui.prose>
            <h2>Rich text from the CMS</h2>
            <p>Body copy is set in the secondary text colour at a comfortable measure. <a href="#prose">Links</a> use an orange underline. <strong>Strong text</strong> returns to the primary colour.</p>
            <ul><li>Lists keep their markers.</li><li>Spacing comes from the prose scale, not from the editor.</li></ul>
            <blockquote>Quotes are marked with a brand rule.</blockquote>
        </x-ui.prose>
    </x-ui.section>
</x-layouts.app>
