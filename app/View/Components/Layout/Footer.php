<?php

namespace App\View\Components\Layout;

use App\Models\SocialLink;
use App\Services\Cms\ContentVersion;
use App\Services\Cms\CtaResolver;
use App\Services\Cms\MenuBuilder;
use App\Services\Cms\Settings;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Footer extends Component
{
    public function __construct(
        private readonly MenuBuilder $menus,
        private readonly Settings $settings,
        private readonly CtaResolver $ctas,
        private readonly ContentVersion $version,
        public bool $minimal = false,
    ) {}

    public function render(): View
    {
        $cta = $this->minimal ? null : $this->ctas->fromSetting('cta.default');

        return view('components.layout.footer', [
            'columns' => $this->minimal ? [] : $this->menus->build('footer'),
            'legal' => $this->menus->build('legal'),
            'social' => SocialLink::query()->visible()->ordered()->get(),
            'companyName' => $this->settings->get('company.name', config('app.name')),
            'tagline' => $this->settings->get('company.tagline'),
            'description' => $this->settings->get('company.description'),
            'email' => $this->settings->get('contact.email'),
            'phone' => $this->settings->get('contact.phone'),
            'address' => $this->settings->get('contact.address'),
            'emailHref' => $this->ctas->emailHref(),
            'phoneHref' => $this->ctas->phoneHref(),
            'cta' => $cta,
            'ctaHref' => $cta ? ($this->ctas->trackedHref($cta) ?? $this->ctas->primaryHref($cta)) : null,
            'ctaSecondaryHref' => $cta ? $this->ctas->secondaryHref($cta) : null,
        ]);
    }
}
