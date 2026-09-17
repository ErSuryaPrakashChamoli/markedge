<?php

namespace App\Analytics;

use App\Attribution\Attribution;
use App\Attribution\AttributionCookie;
use App\Attribution\Normaliser;
use App\Enums\ConversionEventType;
use App\Models\ConversionEvent;
use App\Services\Cms\Settings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Throwable;

/**
 * The one place first-party events are written (Phase 13 §8.1). Applies the privacy rules
 * (consent gate, no personal data, bounded values), bot filtering and the session model so
 * every recorder (page views, search, CTA, leads) behaves the same way.
 */
class Analytics
{
    public function __construct(
        private readonly AttributionCookie $cookie,
        private readonly Settings $settings,
    ) {}

    /**
     * Records an event. Never throws: measurement must not affect the request that produced it.
     *
     * @param  array<string, mixed>  $attributes  path, entity, lead_id, form_id, cta_id, campaign_id, meta
     */
    public function record(ConversionEventType $type, array $attributes = [], ?Request $request = null, ?Attribution $attribution = null): ?ConversionEvent
    {
        try {
            $request ??= request();
            $attribution ??= $request ? $this->cookie->read($request) : null;
            $identified = $request === null || $this->identifiersAllowed($request);
            $entity = $attributes['entity'] ?? null;

            return ConversionEvent::query()->create([
                'type' => $type,
                'lead_id' => $attributes['lead_id'] ?? null,
                'form_id' => $attributes['form_id'] ?? null,
                'cta_id' => $attributes['cta_id'] ?? null,
                'campaign_id' => $attributes['campaign_id'] ?? null,
                'path' => isset($attributes['path']) ? Normaliser::path($attributes['path']) : null,
                'entity_type' => $entity instanceof Model ? $entity->getMorphClass() : ($attributes['entity_type'] ?? null),
                'entity_id' => $entity instanceof Model ? $entity->getKey() : ($attributes['entity_id'] ?? null),
                'visitor_id' => $identified ? $attribution?->visitorId : null,
                'session_id' => $identified ? $attribution?->sessionId : null,
                'source' => $attribution?->last?->source,
                'medium' => $attribution?->last?->medium,
                'campaign' => $attribution?->last?->campaign,
                'meta' => $this->boundedMeta($attributes['meta'] ?? null),
                'created_at' => now(),
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }
    }

    /**
     * Page view for a successful public HTML response. Bots, previews and private surfaces are skipped.
     */
    public function pageView(Request $request, Attribution $attribution, bool $landing, ?Model $entity = null): ?ConversionEvent
    {
        if ($this->isBot($request) || $request->attributes->get('markedge.preview')) {
            return null;
        }

        return $this->record(ConversionEventType::PageViewed, [
            'path' => '/'.ltrim($request->path(), '/'),
            'entity' => $entity,
            'meta' => array_filter(['landing' => $landing ?: null, 'referrer' => Normaliser::referrerHost($request->headers->get('referer'))]),
        ], $request, $attribution);
    }

    public function isBot(Request $request): bool
    {
        $agent = (string) $request->userAgent();

        return $agent === '' || preg_match((string) config('markedge.analytics.bot_pattern'), $agent) === 1;
    }

    /**
     * When attribution requires consent, events are still counted but carry no visitor or session id.
     */
    protected function identifiersAllowed(Request $request): bool
    {
        if (! $this->settings->get('privacy.attribution_requires_consent')) {
            return true;
        }

        return $request->cookies->has('mk_consent');
    }

    /**
     * @param  array<string, mixed>|null  $meta
     * @return array<string, mixed>|null
     */
    protected function boundedMeta(?array $meta): ?array
    {
        if ($meta === null || $meta === []) {
            return null;
        }

        $bounded = [];

        foreach (array_slice($meta, 0, 12, true) as $key => $value) {
            $bounded[mb_substr((string) $key, 0, 40)] = is_string($value) ? mb_substr($value, 0, 160) : (is_scalar($value) || $value === null ? $value : null);
        }

        return $bounded;
    }
}
