@props(['items', 'heading', 'id' => null, 'theme' => 'light'])
@php $rows = collect($items)->filter(fn ($row) => is_array($row) && filled($row['label'] ?? null)); @endphp
@if ($rows->isNotEmpty())
    <x-ui.section :theme="$theme" spacing="sm" :id="$id">
        <h2 class="text-h3 mb-6">{{ $heading }}</h2>
        <dl class="grid gap-6 md:grid-cols-2">
            @foreach ($rows as $row)
                <div class="border-t-2 border-line pt-4">
                    <dt class="text-h4 text-fg">{{ $row['label'] }}</dt>
                    @if (filled($row['text'] ?? null))<dd class="mt-2 text-body-sm text-fg-secondary">{{ $row['text'] }}</dd>@endif
                </div>
            @endforeach
        </dl>
    </x-ui.section>
@endif
