{{-- Places the Livewire lead form; in preview the form is shown but disabled. --}}
@props(['form', 'context' => [], 'heading' => null, 'intro' => null, 'layout' => 'card', 'preview' => false, 'theme' => 'light', 'id' => null, 'container' => 'narrow'])
<x-ui.section :theme="$theme" :id="$id" :container="$container">
    <livewire:lead-form :form="$form" :context="$context" :heading="$heading" :intro="$intro" :layout="$layout" :preview="$preview" :key="'lead-form-'.$form->id.'-'.($id ?? 'main')" />
</x-ui.section>
