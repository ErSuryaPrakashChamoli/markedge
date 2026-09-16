<?php

namespace App\Filament\Pages;

use App\Editorial\ContentInventoryQuery;
use App\Editorial\WorkflowModels;
use App\Enums\PublishStatus;
use App\Models\User;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use UnitEnum;

/**
 * Everything that exists, in one bounded SQL view: type, status, people, dates, indexability.
 */
class ContentInventory extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Editorial';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Content inventory';

    protected string $view = 'filament.pages.content-inventory';

    #[Url]
    public ?string $type = null;

    /** @var array<int, string> */
    #[Url]
    public array $status = [];

    #[Url]
    public ?string $owner = null;

    #[Url]
    public ?string $reviewer = null;

    #[Url]
    public ?string $author = null;

    #[Url]
    public ?string $category = null;

    #[Url]
    public ?string $indexability = null;

    #[Url]
    public ?string $search = null;

    #[Url]
    public ?string $publishedFrom = null;

    #[Url]
    public ?string $publishedUntil = null;

    #[Url]
    public ?string $updatedFrom = null;

    #[Url]
    public ?string $updatedUntil = null;

    #[Url]
    public bool $expired = false;

    #[Url]
    public string $sort = 'updated_at';

    #[Url]
    public int $page = 1;

    public static function canAccess(): bool
    {
        return static::visibleTypes() !== [];
    }

    /**
     * @return array<int, string>
     */
    public static function visibleTypes(): array
    {
        return array_keys(array_filter(WorkflowModels::all(), fn (string $class): bool => Gate::allows('viewAny', $class)));
    }

    public function updated(string $property): void
    {
        if ($property !== 'page') {
            $this->page = 1;
        }
    }

    public function resetFilters(): void
    {
        foreach (['type', 'owner', 'reviewer', 'author', 'category', 'indexability', 'search', 'publishedFrom', 'publishedUntil', 'updatedFrom', 'updatedUntil'] as $property) {
            $this->{$property} = null;
        }

        $this->status = [];
        $this->expired = false;
        $this->page = 1;
    }

    /**
     * @return array<string, mixed>
     */
    protected function filters(): array
    {
        return [
            'type' => $this->type,
            'status' => $this->status,
            'owner' => $this->owner,
            'reviewer' => $this->reviewer,
            'author' => $this->author,
            'category' => $this->category,
            'indexability' => $this->indexability,
            'search' => $this->search,
            'published_from' => $this->publishedFrom,
            'published_until' => $this->publishedUntil,
            'updated_from' => $this->updatedFrom,
            'updated_until' => $this->updatedUntil,
            'expired' => $this->expired,
            'sort' => $this->sort,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        abort_unless(static::canAccess(), 403);

        $rows = app(ContentInventoryQuery::class)->paginate($this->filters(), static::visibleTypes(), 25, $this->page);
        $userIds = collect($rows->items())->flatMap(fn ($row) => [$row->owner_id, $row->reviewer_id, $row->created_by])->filter()->unique();

        return [
            'rows' => $rows,
            'users' => User::query()->orderBy('name')->pluck('name', 'id'),
            'names' => $userIds->isEmpty() ? collect() : User::query()->whereKey($userIds)->pluck('name', 'id'),
            'types' => collect(static::visibleTypes())->mapWithKeys(fn (string $alias) => [$alias => WorkflowModels::label($alias)]),
            'statuses' => collect(PublishStatus::cases())->mapWithKeys(fn (PublishStatus $status) => [$status->value => $status->getLabel()]),
            'queue' => false,
        ];
    }

    public function editUrl(string $type, int $id): ?string
    {
        $class = WorkflowModels::classFor($type);
        $resource = $class ? Filament::getModelResource($class) : null;

        return $resource ? $resource::getUrl('edit', ['record' => $id]) : null;
    }

    public function deskUrl(string $type, int $id): string
    {
        return EditorialDesk::getUrl(['type' => $type, 'record' => $id]);
    }

    public static function indexabilityLabel(object $row): string
    {
        if (filled($row->canonical_url)) {
            return 'canonicalized';
        }

        if ((int) ($row->robots_index ?? ($row->type === 'landing_page' ? 0 : 1)) === 0) {
            return 'noindex';
        }

        return $row->status === 'published' ? 'indexable' : 'not public';
    }
}
