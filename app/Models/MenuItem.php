<?php

namespace App\Models;

use App\Enums\MenuItemType;
use App\Models\Concerns\BumpsContentVersion;
use App\Models\Concerns\HasSortOrder;
use Database\Factories\MenuItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'menu_id', 'parent_id', 'label', 'type', 'linkable_type', 'linkable_id', 'url',
    'description', 'icon', 'badge', 'open_in_new_tab', 'is_visible', 'sort_order', 'settings',
])]
class MenuItem extends Model
{
    /** @use HasFactory<MenuItemFactory> */
    use BumpsContentVersion, HasFactory, HasSortOrder;

    protected function casts(): array
    {
        return [
            'type' => MenuItemType::class,
            'open_in_new_tab' => 'boolean',
            'is_visible' => 'boolean',
            'settings' => 'array',
        ];
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'parent_id')->orderBy('sort_order')->orderBy('id');
    }

    public function linkable(): MorphTo
    {
        return $this->morphTo();
    }

    #[Scope]
    protected function visible(Builder $query): Builder
    {
        return $query->where('is_visible', true);
    }
}
