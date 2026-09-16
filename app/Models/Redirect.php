<?php

namespace App\Models;

use App\Enums\RedirectStatus;
use App\Models\Concerns\BumpsContentVersion;
use App\Models\Concerns\RecordsActivity;
use Database\Factories\RedirectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[Fillable(['from_path', 'to_url', 'status_code', 'is_active', 'notes'])]
class Redirect extends Model
{
    /** @use HasFactory<RedirectFactory> */
    use BumpsContentVersion, HasFactory, RecordsActivity;

    /** @var array<int, string> */
    protected array $activityLogAttributes = ['from_path', 'to_url', 'status_code', 'is_active'];

    protected function casts(): array
    {
        return [
            'status_code' => RedirectStatus::class,
            'is_active' => 'boolean',
            'last_hit_at' => 'datetime',
        ];
    }

    /**
     * Stored as a lowercase, leading-slash, no-trailing-slash path so lookups are exact.
     */
    protected function fromPath(): Attribute
    {
        return Attribute::make(
            set: fn (string $value): string => static::normalisePath($value),
        );
    }

    protected static function booted(): void
    {
        static::saving(function (Redirect $redirect): void {
            $redirect->to_url = trim((string) $redirect->to_url);

            if (! static::isSafeDestination($redirect->to_url)) {
                throw new \InvalidArgumentException('Unsafe redirect destination: '.$redirect->to_url);
            }

            if (str_starts_with($redirect->to_url, '/') && static::normalisePath($redirect->to_url) === $redirect->from_path) {
                throw new \InvalidArgumentException('A redirect cannot point to itself.');
            }
        });
    }

    /**
     * Site paths are always allowed. Absolute URLs must use http(s) and a host that is either the
     * application's own host or on the configured allow-list. Every other scheme is rejected.
     */
    public static function isSafeDestination(string $destination): bool
    {
        $destination = trim($destination);

        if ($destination === '' || preg_match('/[\x00-\x1F\x7F\s]/', $destination)) {
            return false;
        }

        if (str_starts_with($destination, '/')) {
            return ! str_starts_with($destination, '//') && ! str_starts_with($destination, '/\\');
        }

        $parts = parse_url($destination);

        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            return false;
        }

        if (! in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
            return false;
        }

        $host = strtolower($parts['host']);
        $allowed = array_map('strtolower', array_filter([
            parse_url((string) config('app.url'), PHP_URL_HOST),
            ...(array) config('markedge.redirects.allowed_external_hosts', []),
        ]));

        return in_array($host, $allowed, true);
    }

    public static function normalisePath(string $path): string
    {
        $path = Str::of($path)->trim()->lower()->before('?')->before('#')->toString();
        $path = '/'.ltrim($path, '/');

        return $path === '/' ? $path : rtrim($path, '/');
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Follows the chain of active redirects starting at $to and reports whether it
     * returns to $from within the maximum depth (architecture §34).
     */
    public static function createsLoop(string $from, string $to, int|string|null $ignoreId = null, int $maxDepth = 5): bool
    {
        $from = static::normalisePath($from);
        $current = $to;

        for ($hop = 0; $hop < $maxDepth; $hop++) {
            if (! str_starts_with($current, '/')) {
                return false;
            }

            $current = static::normalisePath($current);

            if ($current === $from) {
                return true;
            }

            $next = static::query()
                ->active()
                ->where('from_path', $current)
                ->when($ignoreId, fn (Builder $query) => $query->whereKeyNot($ignoreId))
                ->value('to_url');

            if ($next === null) {
                return false;
            }

            $current = $next;
        }

        return false;
    }
}
