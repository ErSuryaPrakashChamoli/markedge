{{-- Accordion of FAQs. The FAQPage schema for this list is produced by the Phase 6 schema engine. --}}
@props(['faqs', 'heading' => 'Frequently asked questions', 'intro' => null, 'theme' => 'light', 'id' => null])
@if ($faqs->isNotEmpty())
    <x-ui.section :theme="$theme" :id="$id" container="narrow">
        <x-ui.section-header :title="$heading" :intro="$intro" class="mb-8" />
        <div x-data="accordion()" class="divide-y divide-line border-y border-line">
            @foreach ($faqs as $faq)
                <div>
                    <h3>
                        <button type="button" @click="toggle({{ $faq->id }})" :aria-expanded="isActive({{ $faq->id }})" aria-controls="faq-{{ $faq->id }}" class="flex w-full items-center justify-between gap-4 py-5 text-left text-h4 text-fg">
                            {{ $faq->question }}
                            <x-ui.icon name="heroicon-m-chevron-down" class="size-5 shrink-0 text-fg-muted transition-transform" ::class="isActive({{ $faq->id }}) && 'rotate-180'" />
                        </button>
                    </h3>
                    <div id="faq-{{ $faq->id }}" x-show="isActive({{ $faq->id }})" x-collapse x-cloak class="pb-5">
                        <x-ui.prose :html="$faq->answer" />
                    </div>
                </div>
            @endforeach
        </div>
    </x-ui.section>
@endif
