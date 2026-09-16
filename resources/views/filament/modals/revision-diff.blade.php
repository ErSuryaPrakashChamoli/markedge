<div class="space-y-3">
    <p class="text-sm text-gray-500">Comparing v{{ $from->version }} ({{ $from->created_at->format('d M Y H:i') }}) with the current v{{ $to->version }}.</p>
    @if ($changes === [])
        <p class="text-sm">No differences in content, SEO, media or relations.</p>
    @else
        <table class="w-full text-sm">
            <thead><tr class="text-left text-gray-500"><th class="pb-2">Section</th><th class="pb-2">Field</th><th class="pb-2">v{{ $from->version }}</th><th class="pb-2">v{{ $to->version }}</th></tr></thead>
            <tbody>
                @foreach ($changes as $change)
                    <tr class="border-t border-gray-100 align-top dark:border-gray-800">
                        <td class="py-1.5 text-gray-500">{{ $change['section'] }}</td>
                        <td class="py-1.5 font-medium">{{ $change['field'] }}</td>
                        <td class="py-1.5 break-words">{{ $change['from'] }}</td>
                        <td class="py-1.5 break-words">{{ $change['to'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
