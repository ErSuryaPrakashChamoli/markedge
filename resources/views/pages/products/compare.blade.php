{{-- Product comparison (Phase 15). Cells reflect stored modules and features only; a blank cell means "not listed". --}}
<x-layouts.app :meta="$meta">
    <x-sections.entity-hero eyebrow="Products" title="Compare products" intro="Modules and features of each product, side by side, exactly as documented on the product pages." :breadcrumbs="[['label' => 'Products', 'url' => '/products'], ['label' => 'Compare', 'url' => null]]" />

    <x-ui.section>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[640px] text-body-sm">
                <thead>
                    <tr class="border-b-2 border-line text-left">
                        <th class="py-3 pr-4 text-eyebrow text-fg-muted">&nbsp;</th>
                        @foreach ($products as $product)
                            <th class="py-3 pr-4 align-bottom">
                                <a href="{{ url('/products/'.$product->slug) }}" class="text-h4 text-fg hover:text-brand">{{ $product->name }}</a>
                                @if ($product->tagline)<p class="mt-1 text-caption font-normal text-fg-secondary">{{ $product->tagline }}</p>@endif
                                @if ($product->isComingSoon())<p class="mt-1"><x-ui.badge>Coming soon</x-ui.badge></p>@endif
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach (['Modules' => $modules, 'Features' => $features] as $heading => $rows)
                        @if ($rows !== [])
                            <tr><th colspan="{{ $products->count() + 1 }}" class="pt-8 pb-2 text-left text-eyebrow text-brand">{{ $heading }}</th></tr>
                            @foreach ($rows as $row)
                                <tr class="border-t border-line">
                                    <th scope="row" class="py-2.5 pr-4 text-left font-medium text-fg">{{ $row['label'] }}</th>
                                    @foreach ($row['cells'] as $cell)
                                        <td class="py-2.5 pr-4">@if ($cell)<x-ui.icon name="heroicon-m-check" class="size-5 text-brand" /><span class="sr-only">Listed</span>@else<span class="text-fg-muted" aria-label="Not listed">—</span>@endif</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        @endif
                    @endforeach
                    @if (array_filter($deployment))
                        <tr><th colspan="{{ $products->count() + 1 }}" class="pt-8 pb-2 text-left text-eyebrow text-brand">Deployment</th></tr>
                        <tr class="border-t border-line">
                            <th scope="row" class="py-2.5 pr-4 text-left font-medium text-fg">Options listed</th>
                            @foreach ($deployment as $options)
                                <td class="py-2.5 pr-4 text-fg-secondary">{{ $options === [] ? '—' : implode(', ', $options) }}</td>
                            @endforeach
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
        <p class="mt-6 text-caption text-fg-muted">A dash means the item is not listed for that product, not that it is unavailable. Ask for a demo to discuss your requirements.</p>
    </x-ui.section>

    @if ($cta)
        <x-cta.band :cta="$cta" />
    @endif
</x-layouts.app>
