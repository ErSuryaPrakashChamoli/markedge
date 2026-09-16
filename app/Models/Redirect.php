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
}
