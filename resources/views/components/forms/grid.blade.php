{{-- Two-column form grid; fields declare width="half" to share a row on tablet and up. --}}
<div {{ $attributes->merge(['class' => 'grid grid-cols-1 gap-5 md:grid-cols-2']) }}>
    {{ $slot }}
</div>
