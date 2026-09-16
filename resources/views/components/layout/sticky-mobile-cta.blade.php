@inject('ctas', 'App\Services\Cms\CtaResolver')
@php
    $cta = $ctas->fromSetting('cta.header');
    $phone = $ctas->phoneHref();
    $whatsapp = $ctas->whatsappHref();
    $enquire = $cta ? $ctas->primaryHref($cta) : null;
@endphp
@if ($phone || $whatsapp || $enquire)
    <div class="fixed inset-x-0 bottom-0 z-30 border-t border-line bg-surface/95 backdrop-blur lg:hidden" data-theme="light" style="padding-bottom: env(safe-area-inset-bottom)">
        <div class="grid divide-x divide-line" style="grid-template-columns: repeat({{ collect([$phone, $whatsapp, $enquire])->filter()->count() }}, minmax(0, 1fr))">
            @if ($phone)
                <a href="{{ $phone }}" class="flex items-center justify-center gap-2 py-3 text-button text-fg">
                    <x-ui.icon name="heroicon-o-phone" class="size-4" /> Call
                </a>
            @endif
            @if ($whatsapp)
                <a href="{{ $whatsapp }}" target="_blank" rel="noopener" class="flex items-center justify-center gap-2 py-3 text-button text-fg">
                    <x-ui.icon name="heroicon-o-chat-bubble-left-right" class="size-4" /> WhatsApp
                </a>
            @endif
            @if ($enquire)
                <a href="{{ $enquire }}" class="flex items-center justify-center gap-2 bg-brand py-3 text-button text-white">
                    {{ $cta->primary_label }}
                </a>
            @endif
        </div>
    </div>
    <div class="h-14 lg:hidden" aria-hidden="true"></div>
@endif
