@props(['data', 'host' => null, 'preview' => false])
<x-ui.section :theme="$data['theme'] ?? 'light'" :id="$data['anchor'] ?? null">
    @if (filled($data['heading'] ?? null))
        <x-ui.section-header :title="$data['heading']" :intro="$data['intro'] ?? null" class="mb-8" />
    @endif
    <div class="overflow-x-auto rounded-card border border-line">
        <table class="w-full min-w-[40rem] text-body-sm">
            <thead class="bg-canvas-muted">
                <tr>
                    <th scope="col" class="sticky left-0 bg-canvas-muted px-4 py-3 text-left text-eyebrow text-fg-muted"><span class="sr-only">Feature</span></th>
                    @foreach ($data['columns'] as $column)
                        <th scope="col" class="px-4 py-3 text-left text-eyebrow text-fg">{{ is_array($column) ? ($column['label'] ?? '') : $column }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @foreach ($data['rows'] as $row)
                    <tr>
                        <th scope="row" class="sticky left-0 bg-surface px-4 py-3 text-left font-semibold text-fg">{{ $row['label'] }}</th>
                        @foreach ($data['columns'] as $index => $column)
                            @php $value = $row['values'][$index] ?? null; $value = is_array($value) ? ($value['value'] ?? '') : $value; @endphp
                            <td class="px-4 py-3 text-fg-secondary">
                                @if (in_array(strtolower((string) $value), ['yes', 'true', '✓'], true))
                                    <x-ui.icon name="heroicon-m-check" class="size-5 text-success" /><span class="sr-only">Yes</span>
                                @elseif (in_array(strtolower((string) $value), ['no', 'false', '✕', '-'], true))
                                    <x-ui.icon name="heroicon-m-minus" class="size-5 text-fg-muted" /><span class="sr-only">No</span>
                                @else
                                    {{ $value }}
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-ui.section>
