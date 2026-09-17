<?php

namespace App\Services\Cms;

use App\Models\Article;
use App\Models\CaseStudy;
use App\Models\Industry;
use App\Models\LandingPage;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductDocument;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Solution;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;

/**
 * Signed, expiring preview links for unpublished content (architecture §36).
 */
class PreviewLink
{
    /** @var array<string, class-string<Model>> */
    public const array TYPES = [
        'page' => Page::class,
        'service-category' => ServiceCategory::class,
        'service' => Service::class,
        'product' => Product::class,
        'product-document' => ProductDocument::class,
        'solution' => Solution::class,
        'industry' => Industry::class,
        'case-study' => CaseStudy::class,
        'article' => Article::class,
        'landing-page' => LandingPage::class,
    ];

    public function typeFor(Model $record): ?string
    {
        return array_search($record::class, self::TYPES, true) ?: null;
    }

    public function supports(Model $record): bool
    {
        return $this->typeFor($record) !== null;
    }

    public function for(Model $record, ?int $userId = null): string
    {
        $type = $this->typeFor($record) ?? throw new \InvalidArgumentException('Preview is not supported for '.$record::class);

        return URL::temporarySignedRoute(
            'preview.show',
            now()->addHours(config('markedge.preview.ttl_hours', 24)),
            ['type' => $type, 'id' => $record->getKey(), 'by' => $userId ?? auth()->id()],
        );
    }

    public function resolve(string $type, int|string $id): ?Model
    {
        $class = self::TYPES[$type] ?? null;

        if ($class === null) {
            return null;
        }

        $query = $class::query();

        if (method_exists($class, 'withTrashed')) {
            $query->withTrashed();
        }

        return $query->find($id);
    }
}
