<?php

namespace App\Models;

use App\Models\Concerns\BumpsContentVersion;
use App\Models\Concerns\RecordsActivity;
use Database\Factories\SettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Key/value global settings. Values are public configuration only; secrets stay in .env.
 */
#[Fillable(['group', 'key', 'value', 'type'])]
class Setting extends Model implements HasMedia
{
    /** @use HasFactory<SettingFactory> */
    use BumpsContentVersion, HasFactory, InteractsWithMedia, RecordsActivity;

    /** @var array<int, string> */
    protected array $activityLogAttributes = ['key', 'value'];

    protected function casts(): array
    {
        return [
            'value' => 'json',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('file')->singleFile();
    }

    public static function valueOf(string $key, mixed $default = null): mixed
    {
        return static::query()->where('key', $key)->value('value') ?? $default;
    }
}
