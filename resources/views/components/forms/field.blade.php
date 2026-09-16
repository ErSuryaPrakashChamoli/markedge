{{-- Label + control + help/error wrapper. Pass the control in the slot with id="$id". --}}
@props(['id', 'label', 'required' => false, 'help' => null, 'error' => null, 'width' => 'full'])
<div {{ $attributes->merge(['class' => $width === 'half' ? 'md:col-span-1' : 'md:col-span-2']) }}>
    <label for="{{ $id }}" class="mb-1.5 block text-body-sm font-medium text-fg">
        {{ $label }}@if ($required)<span class="text-brand" aria-hidden="true"> *</span>@endif
    </label>
    {{ $slot }}
    @if ($error)
        <p id="{{ $id }}-error" class="mt-1.5 text-caption text-danger" role="alert">{{ $error }}</p>
    @elseif ($help)
        <p id="{{ $id }}-help" class="mt-1.5 text-caption text-fg-muted">{{ $help }}</p>
    @endif
</div>
