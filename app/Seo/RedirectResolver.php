<?php

namespace App\Seo;

use App\Models\Redirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Resolves the redirects table for a request that reached a 404 (architecture §34).
 *
 * Priority is deterministic because from_path is unique: at most one record matches a
 * source, and only active records count. Internal destinations are followed through
 * further active redirects up to max_depth so visitors land on the final URL in one hop.
 */
class RedirectResolver
{
    public function respond(Request $request): ?RedirectResponse
    {
        if (! in_array($request->method(), ['GET', 'HEAD'], true)) {
            return null;
        }

        $resolved = $this->resolve($request->path());

        if ($resolved === null) {
            return null;
        }

        [$redirect, $destination] = $resolved;

        defer(fn () => Redirect::query()->whereKey($redirect->id)->update(['hit_count' => $redirect->hit_count + 1, 'last_hit_at' => now()]));

        return redirect()->to($destination, $redirect->status_code->value)->withHeaders(['X-Robots-Tag' => 'noindex']);
    }

    /**
     * @return array{0: Redirect, 1: string}|null
     */
    public function resolve(string $path): ?array
    {
        $first = Redirect::query()->active()->where('from_path', Redirect::normalisePath($path))->first();

        if ($first === null) {
            return null;
        }

        $destination = $first->to_url;
        $seen = [$first->from_path];
        $maxDepth = (int) config('markedge.redirects.max_depth', 5);

        for ($hop = 0; $hop < $maxDepth; $hop++) {
            if (! str_starts_with($destination, '/')) {
                break;
            }

            $normalised = Redirect::normalisePath($destination);

            if (in_array($normalised, $seen, true)) {
                break;
            }

            $next = Redirect::query()->active()->where('from_path', $normalised)->value('to_url');

            if ($next === null) {
                break;
            }

            $seen[] = $normalised;
            $destination = $next;
        }

        return [$first, Redirect::isSafeDestination($destination) ? $destination : '/'];
    }
}
