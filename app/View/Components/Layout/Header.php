<?php

namespace App\View\Components\Layout;

use App\Services\Cms\CtaResolver;
use App\Services\Cms\MenuBuilder;
use App\Services\Cms\Settings;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Header extends Component
{
    public function __construct(
        private readonly MenuBuilder $menus,
        private readonly Settings $settings,
        private readonly CtaResolver $ctas,
        public bool $minimal = false,
    ) {}

    public function render(): View
    {
        $cta = $this->ctas->fromSetting('cta.header');

        return view('components.layout.header', [
            'items' => $this->minimal ? [] : $this->menus->forPath('header', request()->path()),
            'companyName' => $this->settings->get('company.name', config('app.name')),
            'logoUrl' => $this->settings->logoUrl(),
            'cta' => $cta,
            'ctaHref' => $cta ? $this->ctas->primaryHref($cta) : null,
            'phoneHref' => $this->ctas->phoneHref(),
            'whatsappHref' => $this->ctas->whatsappHref(),
        ]);
    }
}
