@props(['data', 'host' => null, 'preview' => false])
@inject('settings', 'App\Services\Cms\Settings')
@inject('ctas', 'App\Services\Cms\CtaResolver')
<x-ui.section :theme="$data['theme'] ?? 'light'" :id="$data['anchor'] ?? null">
    <x-ui.split ratio="wide-right" align="start">
        <x-slot:left>
            @if (filled($data['heading'] ?? null))
                <h2 class="text-h2">{{ $data['heading'] }}</h2>
            @endif
            @if (filled($data['intro'] ?? null))
                <p class="mt-4 text-body-lg text-fg-secondary">{{ $data['intro'] }}</p>
            @endif
            @if ($data['show_contact_details'] ?? true)
                <x-blocks.partials.contact-details class="mt-8" />
            @endif
        </x-slot:left>
        <x-slot:right>
            <livewire:lead-form :form="$data['form']" :context="$data['context']" :preview="$preview" layout="card" :key="'contact-form-'.$data['form']->id" />
        </x-slot:right>
    </x-ui.split>
</x-ui.section>
