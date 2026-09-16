<x-filament-panels::page>
    <x-filament::section heading="Coverage of published content" description="Counts of live records with their own SEO title and description. Records without one fall back to their content and the global defaults, so gaps are advisory.">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-gray-500">
                    <th class="pb-2">Type</th><th class="pb-2 text-right">Published</th><th class="pb-2 text-right">With SEO title</th><th class="pb-2 text-right">With description</th><th class="pb-2 text-right">No-index</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($coverage as $row)
                    <tr class="border-t border-gray-100 dark:border-gray-800">
                        <td class="py-1.5">{{ $row['type'] }}</td>
                        <td class="py-1.5 text-right">{{ $row['published'] }}</td>
                        <td class="py-1.5 text-right">{{ $row['with_title'] }}</td>
                        <td class="py-1.5 text-right">{{ $row['with_description'] }}</td>
                        <td class="py-1.5 text-right">{{ $row['noindex'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </x-filament::section>

    {{ $this->table }}
</x-filament-panels::page>
