@inject('settings', 'App\Services\Cms\Settings')
@inject('ctas', 'App\Services\Cms\CtaResolver')
@php
    $email = $settings->get('contact.email');
    $phone = $settings->get('contact.phone');
    $address = $settings->get('contact.address');
    $whatsapp = $ctas->whatsappHref();
@endphp
@if ($email || $phone || $address || $whatsapp)
    <dl {{ $attributes->merge(['class' => 'space-y-4 text-body']) }}>
        @if ($email)
            <div><dt class="text-eyebrow text-fg-muted">Email</dt><dd class="mt-1"><a href="{{ $ctas->emailHref() }}" class="font-medium text-fg hover:text-brand">{{ $email }}</a></dd></div>
        @endif
        @if ($phone)
            <div><dt class="text-eyebrow text-fg-muted">Phone</dt><dd class="mt-1"><a href="{{ $ctas->phoneHref() }}" class="font-medium text-fg hover:text-brand">{{ $phone }}</a></dd></div>
        @endif
        @if ($whatsapp)
            <div><dt class="text-eyebrow text-fg-muted">WhatsApp</dt><dd class="mt-1"><a href="{{ $whatsapp }}" target="_blank" rel="noopener" class="font-medium text-fg hover:text-brand">Message us on WhatsApp</a></dd></div>
        @endif
        @if ($address)
            <div><dt class="text-eyebrow text-fg-muted">Address</dt><dd class="mt-1 whitespace-pre-line text-fg-secondary">{{ $address }}</dd></div>
        @endif
    </dl>
@endif
