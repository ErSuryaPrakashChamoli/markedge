<?php

namespace App\Http\Controllers\Site;

use App\Attribution\AttributionCookie;
use App\Attribution\Normaliser;
use App\Enums\ConversionEventType;
use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\ConversionEvent;
use App\Models\Cta;
use App\Models\CtaClick;
use App\Services\Cms\CtaDestination;
use App\Services\Cms\CtaResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

/**
 * GET /go/{cta}/{slot}?p=/page. The destination is always resolved server-side from the CTA
 * record and checked against the safe-destination rules; nothing in the request can redirect
 * a visitor elsewhere. Measurement failures never stop the redirect.
 */
class CtaClickController extends Controller
{
    public function __invoke(Request $request, CtaResolver $ctas, CtaDestination $destinations, AttributionCookie $cookie, string $key, string $slot = 'primary'): RedirectResponse
    {
        abort_unless(in_array($slot, ['primary', 'secondary'], true) && preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $key) === 1, 404);

        $cta = Cta::query()->active()->where('key', $key)->first();
        abort_if($cta === null, 404);

        $entity = is_string($request->query('e')) ? Normaliser::bound($request->query('e')) : null;
        $destination = $slot === 'secondary' ? $ctas->secondaryHref($cta, $entity) : $ctas->primaryHref($cta, $entity);
        $safe = $destinations->safe($destination);
        abort_if($safe === null, 404);

        $response = redirect()->away($safe)->setStatusCode(302);
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');

        try {
            $attribution = $cookie->read($request);
            $path = Normaliser::path(is_string($request->query('p')) ? $request->query('p') : null);
            $action = ($slot === 'secondary' ? $cta->secondary_action : $cta->primary_action)?->value ?? 'url';
            $campaign = $attribution->last?->campaign ? Campaign::query()->matchingUtmCampaign($attribution->last->campaign)->first() : null;

            CtaClick::query()->create([
                'cta_id' => $cta->id,
                'action' => $action,
                'path' => $path,
                'visitor_id' => $attribution->visitorId,
                'target' => mb_substr($safe, 0, 255),
                'source' => $attribution->last?->source,
                'medium' => $attribution->last?->medium,
                'campaign' => $attribution->last?->campaign,
                'campaign_id' => $campaign?->id,
                'created_at' => now(),
            ]);

            ConversionEvent::query()->create([
                'type' => ConversionEventType::CtaClicked,
                'cta_id' => $cta->id,
                'campaign_id' => $campaign?->id,
                'path' => $path,
                'visitor_id' => $attribution->visitorId,
                'source' => $attribution->last?->source,
                'medium' => $attribution->last?->medium,
                'campaign' => $attribution->last?->campaign,
                'meta' => ['slot' => $slot, 'action' => $action],
            ]);

            // Query builder increment: no model events, so no content-cache invalidation.
            Cta::query()->whereKey($cta->id)->increment('click_count');

            $attribution->recordCtaClick($cta->id);

            if ($cookie->allowedFor($request)) {
                $response->headers->setCookie($cookie->make($attribution));
            }
        } catch (Throwable $exception) {
            report($exception);
        }

        return $response;
    }
}
