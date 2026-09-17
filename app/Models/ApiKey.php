<?php

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * API credential. Only a SHA-256 hash is stored; the plain key is shown once at creation.
 */
#[Fillable(['name', 'key_hash', 'prefix', 'abilities', 'is_active', 'expires_at', 'created_by'])]
#[Hidden(['key_hash'])]
class ApiKey extends Model
{
    use RecordsActivity;

    /** @var array<int, string> */
    public const array ABILITIES = ['leads:read', 'leads:write', 'products:read', 'analytics:read'];

    /** @var array<int, string> */
    protected array $activityLogAttributes = ['name', 'abilities', 'is_active', 'expires_at'];

    protected function casts(): array
    {
        return [
            'abilities' => 'array',
            'is_active' => 'boolean',
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Creates a key and returns it with the one-time plain secret.
     *
     * @param  array<int, string>  $abilities
     * @return array{key: self, plain: string}
     */
    public static function issue(string $name, array $abilities, ?int $userId = null, ?\DateTimeInterface $expiresAt = null): array
    {
        $plain = 'mk_'.Str::random(40);

        $key = static::query()->create([
            'name' => $name,
            'key_hash' => hash('sha256', $plain),
            'prefix' => substr($plain, 0, 10),
            'abilities' => array_values(array_intersect($abilities, self::ABILITIES)),
            'is_active' => true,
            'expires_at' => $expiresAt,
            'created_by' => $userId,
        ]);

        return ['key' => $key, 'plain' => $plain];
    }

    public static function findByPlain(?string $plain): ?self
    {
        if (! is_string($plain) || strlen($plain) < 20) {
            return null;
        }

        return static::query()->where('key_hash', hash('sha256', $plain))->first();
    }

    public function can(string $ability): bool
    {
        return in_array($ability, $this->abilities ?? [], true);
    }

    public function isUsable(): bool
    {
        return $this->is_active && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
