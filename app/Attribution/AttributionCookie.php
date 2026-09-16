<?php

namespace App\Attribution;

use App\Services\Cms\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Cookie as SymfonyCookie;

/**
 * Reads and writes the encrypted, HttpOnly, SameSite=Lax first-party attribution cookie.
 */
class AttributionCookie
{
    public const string REQUEST_ATTRIBUTE = 'markedge.attribution';

    public function name(): string
    {
        return (string) config('markedge.attribution.cookie', 'mk_attr');
    }

    /**
     * Attribution for the current request: parsed once and cached on the request.
     */
    public function read(Request $request): Attribution
    {
        $cached = $request->attributes->get(self::REQUEST_ATTRIBUTE);

        if ($cached instanceof Attribution) {
            return $cached;
        }

        $raw = $request->cookie($this->name());
        $data = is_string($raw) ? json_decode($raw, true) : null;
        $attribution = Attribution::fromArray($data) ?? Attribution::fresh();

        $request->attributes->set(self::REQUEST_ATTRIBUTE, $attribution);

        return $attribution;
    }

    public function make(Attribution $attribution): SymfonyCookie
    {
        return Cookie::make(
            name: $this->name(),
            value: json_encode($attribution->toArray(), JSON_THROW_ON_ERROR),
            minutes: (int) config('markedge.attribution.cookie_days', 90) * 1440,
            path: '/',
            domain: config('session.domain'),
            secure: (bool) (config('session.secure') ?? app()->isProduction()),
            httpOnly: true,
            raw: false,
            sameSite: 'lax',
        );
    }

    /**
     * Whether the visitor has allowed a persistent cookie (only enforced when the setting is on).
     */
    public function allowedFor(Request $request): bool
    {
        if (! app(Settings::class)->get('privacy.attribution_requires_consent')) {
            return true;
        }

        return $request->cookies->has('mk_consent');
    }
}
