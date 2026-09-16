{{--
    Renders a host's enabled block tree. Each block key maps to components/blocks/{key}.blade.php;
    until that view exists (Phase 5) a labelled placeholder is shown so previews stay honest.
--}}
@props(['blocks' => [], 'host' => null, 'preview' => false])
@inject('registry', 'App\Cms\Blocks\BlockRegistry')
@foreach ($blocks as $index => $block)
    @php
        $definition = $registry->find($block['type'] ?? '');
        $data = $block['data'] ?? [];
        $view = $definition ? 'components.blocks.'.str_replace('_', '-', $definition->key()) : null;
    @endphp
    @if ($definition && view()->exists($view))
        <x-dynamic-component :component="'blocks.'.str_replace('_', '-', $definition->key())" :data="$data" :host="$host" :preview="$preview" />
    @elseif ($preview)
        <x-ui.section :theme="$data['theme'] ?? 'light'" spacing="sm" :id="$data['anchor'] ?? null">
            <x-ui.card>
                <x-ui.badge tone="brand" class="self-start">Block {{ $index + 1 }}</x-ui.badge>
                <h2 class="mt-3 text-h3">{{ $definition?->label() ?? 'Unknown block' }}</h2>
                @if (filled($data['heading'] ?? $data['headline'] ?? null))
                    <p class="mt-2 text-body-lg text-fg-secondary">{{ $data['heading'] ?? $data['headline'] }}</p>
                @endif
                <p class="mt-3 text-caption text-fg-muted">{{ $definition ? 'This block renders with its public design in the website phase.' : 'This block type is not registered and will not render publicly.' }}</p>
            </x-ui.card>
        </x-ui.section>
    @endif
@endforeach
