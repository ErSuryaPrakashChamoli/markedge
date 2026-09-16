<?php

namespace App\Services\Cms;

use App\Attribution\AttributionCookie;
use App\Models\Campaign;
use App\Models\Cta;

/**
 * Deterministic, explicitly configured personalisation (Phase 9 §35–40): a running campaign
 * with "personalise CTA" enabled swaps the page CTA for its own for visitors whose last touch
 * is that campaign. Nothing indexable changes; canonical, robots, schema and sitemap are untouched.
 */
class CampaignTargeting
{
    private ?Campaign $resolved = null;

    private ?int $resolvedForRequest = null;

    public function __construct(private readonly AttributionCookie $cookie) {}

    public function activeCampaign(): ?Campaign
    {
        $request = request();
        $requestId = $request ? spl_object_id($request) : 0;

        if ($this->resolvedForRequest === $requestId) {
            return $this->resolved;
        }

        $this->resolvedForRequest = $requestId;
        $this->resolved = null;

        if ($request === null || $request->attributes->get('markedge.preview')) {
            return null;
        }

        $utm = $this->cookie->read($request)->last?->campaign;

        if (! is_string($utm) || $utm === '') {
            return null;
        }

        $campaign = Campaign::query()->matchingUtmCampaign($utm)->where('personalize_cta', true)->with('cta')->first();

        return $this->resolved = ($campaign !== null && $campaign->isRunning()) ? $campaign : null;
    }

    public function ctaOverride(): ?Cta
    {
        $cta = $this->activeCampaign()?->cta;

        return $cta !== null && $cta->is_active ? $cta : null;
    }

    /**
     * Plain-language rule shown to admins so targeting is inspectable.
     */
    public static function explain(Campaign $campaign): string
    {
        if (! $campaign->personalize_cta) {
            return 'Visitors from this campaign see the default CTAs.';
        }

        $cta = $campaign->cta?->name ?? 'no CTA selected (default shown)';

        return "While the campaign is running, visitors whose last touch is utm_campaign={$campaign->utm_campaign} see the CTA \"{$cta}\" on every page. Everyone else, search engines and previews see the default CTA.";
    }

    public function forget(): void
    {
        $this->resolvedForRequest = null;
        $this->resolved = null;
    }
}
