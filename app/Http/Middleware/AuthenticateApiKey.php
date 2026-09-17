<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bearer API-key authentication with per-key abilities. Keys are compared by hash; a failed
 * lookup never reveals whether the key exists or expired.
 */
class AuthenticateApiKey
{
    public const string ATTRIBUTE = 'markedge.api_key';

    public function handle(Request $request, Closure $next, ?string $ability = null): Response
    {
        $key = $request->attributes->get(self::ATTRIBUTE) ?? ApiKey::findByPlain($request->bearerToken());

        if ($key === null || ! $key->isUsable()) {
            return response()->json(['message' => 'Unauthenticated.'], 401)->header('WWW-Authenticate', 'Bearer');
        }

        if ($ability !== null && ! $key->can($ability)) {
            return response()->json(['message' => 'This API key is not allowed to '.$ability.'.'], 403);
        }

        if (! $request->attributes->has(self::ATTRIBUTE)) {
            $request->attributes->set(self::ATTRIBUTE, $key);

            if ($key->last_used_at === null || $key->last_used_at->lt(now()->subMinutes(5))) {
                $key->forceFill(['last_used_at' => now()])->saveQuietly();
            }
        }

        return $next($request);
    }

    public static function ability(string $ability): string
    {
        return static::class.':'.$ability;
    }
}
